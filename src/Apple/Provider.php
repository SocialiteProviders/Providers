<?php

namespace SocialiteProviders\Apple;

use Firebase\JWT\JWK;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\InvalidStateException;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'APPLE';

    private const URL = 'https://appleid.apple.com';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'name',
        'email',
    ];

    /**
     * {@inheritdoc}
     */
    protected $encodingType = PHP_QUERY_RFC3986;

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(self::URL.'/auth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
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
            RequestOptions::HEADERS        => ['Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret)],
            RequestOptions::FORM_PARAMS    => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        static::verify($token);
        $claims = explode('.', $token)[1];

        return json_decode(base64_decode($claims), true);
    }

    /**
     * Verify Apple jwt.
     *
     * @param string $jwt
     *
     * @return bool
     *
     * @see https://appleid.apple.com/auth/keys
     */
    public static function verify($jwt)
    {
        $jwtContainer = Configuration::forUnsecuredSigner();
        $token = $jwtContainer->parser()->parse($jwt);

        $data = Cache::remember('socialite:Apple-JWKSet', 5 * 60, function () {
            $response = (new Client())->get(self::URL.'/auth/keys');

            return json_decode((string) $response->getBody(), true);
        });

        $publicKeys = JWK::parseKeySet($data);
        $kid = $token->headers()->get('kid');

        if (isset($publicKeys[$kid])) {
            $publicKey = openssl_pkey_get_details($publicKeys[$kid]);
            $constraints = [
                new SignedWith(new Sha256(), InMemory::plainText($publicKey['key'])),
                new IssuedBy(self::URL),
                new LooseValidAt(SystemClock::fromSystemTimezone()),
            ];

            try {
                $jwtContainer->validator()->assert($token, ...$constraints);

                return true;
            } catch (RequiredConstraintsViolated $e) {
                throw new InvalidStateException($e->getMessage());
            }
        }

        throw new InvalidStateException('Invalid JWT Signature');
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
                $this->request->session()->put('state', $state);
                $this->request->session()->put('state_verify', $state);
            }

            if ($this->hasInvalidState()) {
                throw new InvalidStateException();
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

        return (new User())
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
}
