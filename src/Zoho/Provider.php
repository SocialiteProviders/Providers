<?php

namespace SocialiteProviders\Zoho;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'ZOHO';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['aaaserver.profile.READ'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://accounts.zoho.com/oauth/v2/auth', $state);
    }

    /**
     * Gets the Accounts Server to use from Zoho provider.
     */
    protected function getAccountsServerUrl()
    {
        return $this->request->input('accounts-server', 'https://accounts.zoho.com');
    }

    protected function getTokenUrl(): string
    {
        return $this->getAccountsServerUrl().'/oauth/v2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getAccountsServerUrl().'/oauth/user/info', [
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
            'id'       => $user['ZUID'],
            'email'    => $user['Email'],
            'nickname' => $user['Display_Name'],
            'name'     => $user['First_Name'].' '.$user['Last_Name'],
            'avatar'   => ! empty($user['images']) ? $user['images'][0]['url'] : null,
        ]);
    }
}
