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
            $this->getBaseApiUrl().'User/currentPrincipal',
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

    private function getBaseApiUrl(): string
    {
        $environment = $this->getConfig('environment', 'sod');
        $customerId = $this->getConfig('customer_id');

        return cache()->remember('superoffice-base-url', now()->addHours(8), function () use ($environment, $customerId) {
            $url = sprintf(
                'https://%s.superoffice.com/api/state/%s',
                $environment,
                $customerId
            );

            $response = $this->getHttpClient()->get($url);
            $apiUrl = json_decode((string) $response->getBody(), true)['Api'];

            if (!$apiUrl) {
                throw new \Exception('No API URL received from '.$url);
            }

            return $apiUrl.'/v1/';
        });
    }
}
