<?php

namespace SocialiteProviders\Aweber;

use League\OAuth1\Client\Credentials\TokenCredentials;
use SocialiteProviders\Manager\OAuth1\Server as BaseServer;
use SocialiteProviders\Manager\OAuth1\User;

class Server extends BaseServer
{
    /**
     * @var string
     */
    public $authBaseUrl = 'https://auth.aweber.com/1.0/oauth/';

    /**
     * {@inheritdoc}
     */
    public function urlTemporaryCredentials()
    {
        return $this->authBaseUrl.'request_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlAuthorization()
    {
        return $this->authBaseUrl.'authorize';
    }

    /**
     * {@inheritdoc}
     */
    public function urlTokenCredentials()
    {
        return $this->authBaseUrl.'access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlUserDetails()
    {
        return 'https://api.aweber.com/1.0/accounts';
    }

    /**
     * {@inheritdoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        $data = $data['entries'][0];

        $user = new User;
        $user->id = $data['id'];
        $user->extra = array_diff_key($data, array_flip([
            'id',
        ]));

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return $data['entries'][0]['id'];
    }

    /**
     * {@inheritdoc}
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return null;
    }
}
