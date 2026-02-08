<?php

namespace SocialiteProviders\Apple;

use Carbon\CarbonImmutable;
use DateInterval;
use Firebase\JWT\JWK;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\InvalidStateException;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Exception;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Ecdsa\Sha256 as EcdsaSha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256 as RsaSha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use Psr\Http\Message\ResponseInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'APPLE';

    private const URL = 'https://appleid.apple.com';

    protected $scopes = [
        'name',
        'email',
    ];

    /**
     * {@inheritdoc}
     */
    protected $encodingType = PHP_QUERY_RFC3986;

    protected $scopeSeparator = ' ';

    /**
     * JWT Configuration for Apple Authentication Token.
     *
     * @var ?Configuration
     */
    protected ?Configuration $jwtConfig = null;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(self::URL.'/auth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return self::URL.'/auth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = [
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUrl,
            'scope'         => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'response_type' => 'code',
            'response_mode' => 'form_post',
        ];

        if ($this->usesState()) {
            $fields['state'] = $state;
            $fields['nonce'] = Str::uuid().'.'.$state;
        }

        return array_merge($fields, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS        => ['Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->getClientSecret())],
            RequestOptions::FORM_PARAMS    => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $this->checkToken($token);
        $claims = explode('.', $token)[1];

        return json_decode(base64_decode($claims), true);
    }

    protected function getClientSecret()
    {
        if (!empty($this->privateKey)) {
            $this->clientSecret = $this->generateApplePrivateTokenString();
            config()->set('services.apple.client_secret', $this->clientSecret);
        }

        return $this->clientSecret;
    }

    protected function createJwtConfig(): void
    {
        if (!$this->jwtConfig instanceof Configuration) {
            $private_key_path = $this->getConfig('private_key', '');
            $private_key_passphrase = $this->getConfig('passphrase', '');
            $signerClassName = $this->getConfig('signer', '');

            if (empty($signerClassName) || !class_exists($signerClassName) || !is_a($signerClassName, Signer::class, true)) {
                $signerClassName = EcdsaSha256::class;
            }

            if (!empty($private_key_path) && file_exists($private_key_path)) {
                $key = InMemory::file($private_key_path, $private_key_passphrase);
            } else {
                $key = InMemory::plainText($private_key_path, $private_key_passphrase);
            }

            $this->jwtConfig = Configuration::forSymmetricSigner(
                new $signerClassName(),
                $key
            );
        }
    }

    private function generateApplePrivateTokenString(): string
    {
        $now = CarbonImmutable::now();
        $this->createJwtConfig();

        $token = $this->jwtConfig->builder()
            ->issuedBy(config('services.apple.team_id'))
            ->issuedAt($now)
            ->expiresAt($now->addHour())
            ->permittedFor(Provider::URL)
            ->relatedTo(config('services.apple.client_id'))
            ->withHeader('kid', config('services.apple.key_id'))
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        return $token->toString();
    }

    /**
     * Return the user given the identity token provided on the client
     * side by Apple.
     *
     * @param  string  $token
     * @return User $user
     *
     * @throws InvalidStateException when token can't be parsed
     */
    public function userByIdentityToken(string $token): User
    {
        $array = $this->getUserByToken($token);

        return $this->mapUserToObject($array);
    }

    /**
     * Verify Apple JWT.
     *
     * @param  string  $jwt
     * @return bool
     *
     * @see https://appleid.apple.com/auth/keys
     */
    public function checkToken($jwt)
    {
        try {
            $token = (new Parser(new JoseEncoder()))->parse($jwt);
        } catch (Exception $e) {
            throw new InvalidStateException($e->getMessage());
        }

        $data = Cache::remember('socialite:Apple-JWKSet', 5 * 60, function () {
            $response = (new Client)->get(self::URL.'/auth/keys');

            return json_decode((string) $response->getBody(), true);
        });

        $publicKeys = JWK::parseKeySet($data);
        $kid = $token->headers()->get('kid');

        if (!isset($publicKeys[$kid])) {
            throw new InvalidStateException('Invalid JWT Signature');
        }

        $publicKey = openssl_pkey_get_details($publicKeys[$kid]->getKeyMaterial());
        try {
            $constraints = [
                new SignedWith(new RsaSha256, InMemory::plainText($publicKey['key'])),
                new IssuedBy(self::URL),
                new LooseValidAt(SystemClock::fromSystemTimezone(), new DateInterval('PT3S')),
            ];

            (new Validator())->assert($token, ...$constraints);
        } catch (Exception $e) {
            throw new InvalidStateException($e->getMessage());
        }
        return true;
    }

    /**
     * Verify Apple jwt via static function.
     *
     * @param string $jwt
     *
     * @return bool
     *
     * @see https://appleid.apple.com/auth/keys
     */
    public static function verify($jwt)
    {
        return (new self(
            new Request(),
            config('services.apple.client_id'),
            config('services.apple.client_secret'),
            config('services.apple.redirect')
        ))->checkToken($jwt);
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        //Temporary fix to enable stateless
        $response = $this->getAccessTokenResponse($this->getCode());

        $appleUserToken = $this->getUserByToken(
            $token = Arr::get($response, 'id_token')
        );

        if ($this->usesState()) {
            $state = explode('.', $appleUserToken['nonce'])[1];
            if ($state === $this->request->input('state')) {
                $this->request->session()->put([
                    'state'        => $state,
                    'state_verify' => $state,
                ]);
            }

            if ($this->hasInvalidState()) {
                throw new InvalidStateException;
            }
        }

        $user = $this->mapUserToObject($appleUserToken);

        if ($user instanceof User) {
            $user->setAccessTokenResponseBody($response);
        }

        return $user->setToken($token)
            ->setRefreshToken(Arr::get($response, 'refresh_token'))
            ->setExpiresIn(Arr::get($response, 'expires_in'));
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $userRequest = $this->getUserRequest();

        if (isset($userRequest['name'])) {
            $user['name'] = $userRequest['name'];
            $fullName = trim(
                ($user['name']['firstName'] ?? '')
                .' '
                .($user['name']['lastName'] ?? '')
            );
        }

        return (new User)
            ->setRaw($user)
            ->map([
                'id'    => $user['sub'],
                'name'  => $fullName ?? null,
                'email' => $user['email'] ?? null,
            ]);
    }

    private function getUserRequest(): array
    {
        $value = $this->request->input('user');

        if (is_array($value)) {
            return $value;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return [];
        }

        return json_decode($value, true);
    }

    /**
     * @return string
     */
    protected function getRevokeUrl(): string
    {
        return self::URL.'/auth/revoke';
    }

    /**
     * @param  string  $token
     * @param  string  $hint
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function revokeToken(string $token, string $hint = 'access_token')
    {
        return $this->getHttpClient()->post($this->getRevokeUrl(), [
            RequestOptions::FORM_PARAMS => [
                'client_id'       => $this->clientId,
                'client_secret'   => $this->getClientSecret(),
                'token'           => $token,
                'token_type_hint' => $hint,
            ],
        ]);
    }

    /**
     * Acquire a new access token using the refresh token.
     *
     * Refer to the documentation for the response structure (the `refresh_token` will be missing from the new response).
     *
     * @see https://developer.apple.com/documentation/sign_in_with_apple/tokenresponse
     *
     * @param  string  $refreshToken
     * @return ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refreshToken($refreshToken): ResponseInterface
    {
        return $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => [
                'client_id'       => $this->clientId,
                'client_secret'   => $this->clientSecret,
                'grant_type'      => 'refresh_token',
                'refresh_token'   => $refreshToken,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['private_key', 'passphrase', 'signer'];
    }
}
