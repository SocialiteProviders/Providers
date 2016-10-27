<?php

namespace SocialiteProviders\Twitter;

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
        return 'https://api.twitter.com/oauth/request_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlAuthorization()
    {
        return 'https://api.twitter.com/oauth/authenticate';
    }

    /**
     * {@inheritdoc}
     */
    public function urlTokenCredentials()
    {
        return 'https://api.twitter.com/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlUserDetails()
    {
        return 'https://api.twitter.com/1.1/account/verify_credentials.json?include_email=true';
    }

    /**
     * {@inheritdoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        $user = new User();
        $user->id = $data['id'];
        $user->nickname = $data['screen_name'];
        $user->name = $data['name'];
        $user->location = $data['location'];
        $user->description = $data['description'];
        $user->avatar = $data['profile_image_url'];
        $user->email = null;

        if (isset($data['email'])) {
            $user->email = $data['email'];
        }

        $used = ['id', 'screen_name', 'name', 'location', 'description', 'profile_image_url', 'email'];

        foreach ($data as $key => $value) {
            if (strpos($key, 'url') !== false) {
                if (!in_array($key, $used)) {
                    $used[] = $key;
                }

                $user->urls[$key] = $value;
            }
        }

        $user->extra = array_diff_key($data, array_flip($used));

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
        return $data['screen_name'];
    }
}
