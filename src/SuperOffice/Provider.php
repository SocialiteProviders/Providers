<?php

namespace SocialiteProviders\SuperOffice;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'SUPEROFFICE';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['openid'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return
            $this->buildAuthUrlFromBase(
                sprintf(
                    'https://%s.superoffice.com/login/common/oauth/authorize',
                    $this->config['environment'] ?: 'sod'
                ),
                $state
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return sprintf(
            'https://%s.superoffice.com/login/common/oauth/tokens',
            $this->config['environment'] ?: 'sod'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code): array
    {
        return array_merge(
            parent::getTokenFields($code),
            [
                'grant_type' => 'authorization_code',
            ]
        );
    }

    /**
     * @param string $token
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return array
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get(
            sprintf(
                'https://%s.superoffice.com/%s/api/v1/User/currentPrincipal',
                $this->config['environment'] ?: 'sod',
                $this->config['customer_id']
            ),
            [
                'headers' => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return (array) json_decode($response->getBody());
    }

    protected function mapUserToObject(array $user): \SocialiteProviders\Manager\OAuth2\User
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['EjUserId'],
            'name'     => $user['FullName'],
            'email'    => $user['EMailAddress'],
            'username' => $user['UserName'],
        ]);
    }

    /**
     * Add the additional configuration keys 'environment' and 'customer_id'.
     *
     * @return array
     */
    public static function additionalConfigKeys(): array
    {
        return [
            'environment',
            'customer_id',
        ];
    }
}
