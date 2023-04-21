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
        $cache_time = 60 * 60 * 8; // 8 hours
        $environment = $this->getConfig('environment', 'sod');
        $customer_id = $this->getConfig('customer_id');

        return cache()->remember('superoffice-base-url', $cache_time, function () use($environment, $customer_id) {
            $url = sprintf(
                'https://%s.superoffice.com/api/state/%s',
                $environment,
                $customer_id
            );

            $response = $this->getHttpClient()->get($url);
            $api_url = json_decode((string) $response->getBody(), true)['Api'];

            if(!$api_url)
            {
                throw new \Exception('No API URL received from '. $url);
            }

            return $api_url.'/v1/';
        });
    }
}
