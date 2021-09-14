<?php

namespace SocialiteProviders\AzureADB2C;

use Firebase\JWT\JWK;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'AZUREADB2C';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'openid',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            sprintf(
                'https://%s.b2clogin.com/%s.onmicrosoft.com/%s/oauth2/v2.0/authorize',
                $this->getConfig('domain'),
                $this->getConfig('domain'),
                $this->getConfig('policy')
            ),
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return sprintf(
            'https://%s.b2clogin.com/%s.onmicrosoft.com/%s/oauth2/v2.0/token',
            $this->getConfig('domain'),
            $this->getConfig('domain'),
            $this->getConfig('policy')
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $this->verifyIdToken($token);
        $claims = explode('.', $token)[1];

        return json_decode(base64_decode($claims), true);
    }

    private function verifyIdToken($jwt)
    {
        $jwtContainer = Configuration::forUnsecuredSigner();
        $token = $jwtContainer->parser()->parse($jwt);

        $data = Cache::remember('socialite:AzureADB2C-JWKSet', 5 * 60, function () {
            $response = (new Client())->get(
                sprintf(
                    'https://%s.b2clogin.com/%s.onmicrosoft.com/%s/discovery/v2.0/keys',
                    $this->getConfig('domain'),
                    $this->getConfig('domain'),
                    $this->getConfig('policy')
                )
            );

            return json_decode((string) $response->getBody(), true);
        });

        $publicKeys = JWK::parseKeySet($data);
        $kid = $token->headers()->get('kid');

        if (isset($publicKeys[$kid])) {
            $publicKey = openssl_pkey_get_details($publicKeys[$kid]);
            $constraints = [
                new SignedWith(new Sha256(), InMemory::plainText($publicKey['key'])),
                new IssuedBy(
                    sprintf(
                        'https://%s.b2clogin.com/%s/v2.0/',
                        $this->getConfig('domain'),
                        $this->getConfig('tenantid')
                    )
                ),
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


    public function user()
    {
        $response = $this->getAccessTokenResponse($this->getCode());

        $claims = $this->getUserByToken(
            Arr::get($response, 'id_token')
        );

        return $this->mapUserToObject($claims);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'   => $user['sub'],
            'name' => $user['name'],
        ]);
    }

    /**
     * @return array
     */
    public static function additionalConfigKeys()
    {
        return [
            'domain',
            'policy',
            'tenantid',
        ];
    }
}
