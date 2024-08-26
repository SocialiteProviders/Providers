<?php

namespace SocialiteProviders\Readability;

use League\OAuth1\Client\Credentials\TokenCredentials;
use SocialiteProviders\Manager\OAuth1\Server as BaseServer;
use SocialiteProviders\Manager\OAuth1\User;

class Server extends BaseServer
{
    /**
     * {@inheritdoc}
     */
    public function urlTemporaryCredentials()
    {
        return 'https://www.readability.com/api/rest/v1/oauth/request_token/';
    }

    /**
     * {@inheritdoc}
     */
    public function urlAuthorization()
    {
        return 'https://www.readability.com/api/rest/v1/oauth/authorize/';
    }

    /**
     * {@inheritdoc}
     */
    public function urlTokenCredentials()
    {
        return 'https://www.readability.com/api/rest/v1/oauth/access_token/';
    }

    /**
     * {@inheritdoc}
     */
    public function urlUserDetails()
    {
        return 'https://www.readability.com/api/rest/v1/users/_current';
    }

    /**
     * {@inheritdoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        $user = new User;
        $user->nickname = $data['username'];
        $user->name = $data['first_name'].' '.$data['last_name'];
        $user->email = $data['email_into_address'];
        $user->avatar = $data['avatar_url'];
        $user->extra = array_diff_key($data, array_flip([
            'username', 'first_name', 'last_name',
            'email_into_address', 'avatar_url',
        ]));

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function userUid($data, TokenCredentials $tokenCredentials) {}

    /**
     * {@inheritdoc}
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return $data['email_into_address'];
    }

    /**
     * {@inheritdoc}
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return $data['username'];
    }
}
