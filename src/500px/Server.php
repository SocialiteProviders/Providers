<?php

namespace SocialiteProviders\FiveHundredPixel;

use SocialiteProviders\Manager\OAuth1\User;
use League\OAuth1\Client\Credentials\TokenCredentials;
use SocialiteProviders\Manager\OAuth1\Server as BaseServer;

class Server extends BaseServer
{
    /**
     * {@inheritdoc}
     */
    public function urlTemporaryCredentials()
    {
        return 'https://api.500px.com/v1/oauth/request_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlAuthorization()
    {
        return 'https://api.500px.com/v1/oauth/authorize';
    }

    /**
     * {@inheritdoc}
     */
    public function urlTokenCredentials()
    {
        return 'https://api.500px.com/v1/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlUserDetails()
    {
        return 'https://api.500px.com/v1/users';
    }

    /**
     * {@inheritdoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        $data = $data['user'];

        $user = new User();
        $user->id = $data['id'];
        $user->nickname = $data['username'];
        $user->name = $data['fullname'];
        $user->email = $data['email'];
        $user->avatar = $data['userpic_url'];
        $user->extra = array_diff_key($data, array_flip([
            'id', 'username', 'fullname', 'email', 'userpic_url',
        ]));

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return $data['users'][0]['id'];
    }

    /**
     * {@inheritdoc}
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return $data['users'][0]['email'];
    }

    /**
     * {@inheritdoc}
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return $data['users'][0]['username'];
    }
}
