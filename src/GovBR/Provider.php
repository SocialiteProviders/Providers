<?php

namespace SocialiteProviders\GovBR;

use GuzzleHttp\RequestOptions;
use RuntimeException;
use SocialiteProviders\Manager\Contracts\OAuth2\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    public const IDENTIFIER = 'GOVBR';

    public const SCOPE_OPENID = 'openid';

    public const SCOPE_EMAIL = 'email';

    public const SCOPE_PROFILE = 'profile';

    public const SCOPE_GOVBR_EMPRESA = 'govbr_empresa';

    public const SCOPE_GOVBR_CONFIABILIDADES = 'govbr_confiabilidades';

    /**
     * Staging URL.
     *
     * @var string
     */
    protected $stagingUrl = 'https://sso.staging.acesso.gov.br';

    /**
     * Production URL.
     *
     * @var string
     */
    protected $productionUrl = 'https://sso.acesso.gov.br';

    protected $scopeSeparator = ' ';

    protected $scopes = [
        self::SCOPE_OPENID,
        self::SCOPE_EMAIL,
        self::SCOPE_PROFILE,
        self::SCOPE_GOVBR_CONFIABILIDADES,
    ];

    /**
     * {@inheritdoc}
     */
    protected $usesPKCE = true;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrlForEnvironment().'/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUrlForEnvironment().'/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrlForEnvironment().'/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'                    => $user['sub'],
            'cpf'                   => $user['sub'],
            'name'                  => $user['name'],
            'email'                 => $user['email'] ?? null,
            'email_verified'        => $user['email_verified'] ?? null,
            'phone_number'          => $user['phone_number'] ?? null,
            'phone_number_verified' => $user['phone_number_verified'] ?? null,
            'avatar_url'            => $user['picture'] ?? null,
            'profile'               => $user['profile'] ?? null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['environment'];
    }

    /**
     * Get the URL for the given environment.
     *
     * @throws RuntimeException
     */
    protected function getBaseUrlForEnvironment(): string
    {
        $environment = $this->getConfig('environment', 'production');

        return match ($environment) {
            'staging'    => $this->stagingUrl,
            'production' => $this->productionUrl,
            default      => throw new RuntimeException("Invalid environment '{$environment}' selected for GovBR provider."),
        };
    }
}
