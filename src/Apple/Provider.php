<?php

namespace SocialiteProviders\Apple;

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
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Psr\Http\Message\ResponseInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'APPLE';

    public const URL = 'https://appleid.apple.com';

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
     * JWT Configuration.
     *
     * @var ?Configuration
     */
    protected $jwtConfig = null;

    /**
     * Private Key.
     *
     * @var string
     */
    protected $privateKey = '';

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
        if (!$this->jwtConfig) {
            $this->getJwtConfig(); // Generate Client Secret from private key if not set.
        }

        return $this->clientSecret;
    }

    protected function getJwtConfig()
    {
        if (!$this->jwtConfig) {
            $private_key_path = $this->getConfig('private_key', '');
            $private_key_passphrase = $this->getConfig('passphrase', '');
            $signer = $this->getConfig('signer', '');

            if (empty($signer) || !class_exists($signer)) {
                $signer = !empty($private_key_path) ? \Lcobucci\JWT\Signer\Ecdsa\Sha256::class : AppleSignerNone::class;
            }

            if (!empty($private_key_path) && file_exists($private_key_path)) {
                $this->privateKey = file_get_contents($private_key_path);
            } else {
                $this->privateKey = $private_key_path; // Support for plain text private keys
            }

            $this->jwtConfig = Configuration::forSymmetricSigner(
                new $signer(),
                AppleSignerInMemory::plainText($this->privateKey, $private_key_passphrase)
            );

            if (!empty($this->privateKey)) {
                $appleToken = new AppleToken($this->getJwtConfig(), $this->getConfig('team_id', ''), $this->clientId, $this->getConfig('key_id', ''));
                $this->clientSecret = $appleToken->generate();
                config()->set('services.apple.client_secret', $this->clientSecret);
            }
        }

        return $this->jwtConfig;
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
        $token = $this->getJwtConfig()->parser()->parse($jwt);

        $data = Cache::remember('socialite:Apple-JWKSet', 5 * 60, function () {
            $response = (new Client)->get(self::URL.'/auth/keys');

            return json_decode((string) $response->getBody(), true);
        });

        $publicKeys = JWK::parseKeySet($data);
        $kid = $token->headers()->get('kid');

        if (isset($publicKeys[$kid])) {
            $publicKey = openssl_pkey_get_details($publicKeys[$kid]->getKeyMaterial());
            $constraints = [
                new SignedWith(new Sha256, AppleSignerInMemory::plainText($publicKey['key'])),
                new IssuedBy(self::URL),
                // fix for #1354
                new LooseValidAt(SystemClock::fromSystemTimezone(), new DateInterval('PT3S')),
            ];

            try {
                $this->jwtConfig->validator()->assert($token, ...$constraints);

                return true;
            } catch (RequiredConstraintsViolated $e) {
                throw new InvalidStateException($e->getMessage());
            }
        }

        throw new InvalidStateException('Invalid JWT Signature');
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
        return ['private_key', 'passphrase', 'signer', 'team_id', 'key_id'];
    }
}
