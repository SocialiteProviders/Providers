<?php

namespace SocialiteProviders\Tumblr;

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
        return 'https://www.tumblr.com/oauth/request_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlAuthorization()
    {
        return 'https://www.tumblr.com/oauth/authorize';
    }

    /**
     * {@inheritdoc}
     */
    public function urlTokenCredentials()
    {
        return 'https://www.tumblr.com/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlUserDetails()
    {
        return 'https://api.tumblr.com/v2/user/info';
    }

    /**
     * {@inheritdoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        // If the API has broke, return nothing
        if (! isset($data['response']['user']) || ! is_array($data['response']['user'])) {
            return;
        }

        $data = $data['response']['user'];

        $user = new User();

        $user->nickname = $data['name'];

        // Save all extra data
        $used = ['name'];
        $user->extra = array_diff_key($data, array_flip($used));

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        if (! isset($data['response']['user']) || ! is_array($data['response']['user'])) {
            return;
        }

        $data = $data['response']['user'];

        return $data['name'];
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
        if (! isset($data['response']['user']) || ! is_array($data['response']['user'])) {
            return;
        }

        $data = $data['response']['user'];

        return $data['name'];
    }
}
