<?php

namespace SocialiteProviders\SuperOffice;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
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
                    $this->getConfig('environment', 'sod')
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
            $this->getConfig('environment', 'sod')
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
                $this->getConfig('environment', 'sod'),
                $this->getConfig('customer_id')
            ),
            [
                RequestOptions::HEADERS => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return (array) json_decode((string) $response->getBody(), true);
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
     * {@inheritdoc}
     */
    public static function additionalConfigKeys(): array
    {
        return [
            'environment',
            'customer_id',
        ];
    }
}
