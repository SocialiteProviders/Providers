<?php

namespace SocialiteProviders\Discogs;

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
        return 'https://api.discogs.com/oauth/request_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlAuthorization()
    {
        return 'https://discogs.com/oauth/authorize';
    }

    /**
     * {@inheritdoc}
     */
    public function urlTokenCredentials()
    {
        return 'https://api.discogs.com/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlUserDetails()
    {
        return 'https://api.discogs.com/oauth/identity';
    }

    /**
     * {@inheritdoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        $user = new User();
        $user->id = $data['id'];
        $user->nickname = $data['username'];
        $user->extra = array_diff_key($data, array_flip(['id', 'username']));

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return $data['id'];
    }

    /**
     * {@inheritdoc}
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return $data['username'];
    }
}
