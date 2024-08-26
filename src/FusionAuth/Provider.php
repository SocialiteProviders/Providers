<?php

namespace SocialiteProviders\FusionAuth;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'FUSIONAUTH';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'email',
        'openid',
        'profile',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * Get the base URL.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getFusionAuthUrl()
    {
        $baseUrl = $this->getConfig('base_url');

        if ($baseUrl === null) {
            throw new InvalidArgumentException('Missing base_url');
        }

        return rtrim($baseUrl).'/oauth2';
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return [
            'base_url',
            'tenant_id',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getFusionAuthUrl().'/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getFusionAuthUrl().'/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getFusionAuthUrl().'/userinfo', [
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
            'id'       => $user['sub'],
            'nickname' => $user['preferred_username'] ?? null,
            'name'     => $user['name'] ?? trim(($user['given_name'] ?? '').' '.($user['family_name'] ?? '')),
            'email'    => $user['email'],
            'avatar'   => $user['picture'] ?? null,
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);

        $tenantId = $this->getConfig('tenant_id');
        if (! empty($tenantId)) {
            $fields['tenantId'] = $tenantId;
        }

        return $fields;
    }
}
