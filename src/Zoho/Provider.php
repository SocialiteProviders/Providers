<?php

namespace SocialiteProviders\Zoho;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'ZOHO';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['aaaserver.profile.READ'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
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

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getAccountsServerUrl().'/oauth/v2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getAccountsServerUrl().'/oauth/user/info', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['ZUID'],
            'email'    => $user['Email'],
            'nickname' => $user['Display_Name'],
            'name'     => $user['First_Name'].' '.$user['Last_Name'],
            'avatar'   => !empty($user['images']) ? $user['images'][0]['url'] : null,
        ]);
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
}
