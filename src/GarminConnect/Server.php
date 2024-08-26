<?php

namespace SocialiteProviders\GarminConnect;

use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use SocialiteProviders\Manager\OAuth1\Server as BaseServer;
use SocialiteProviders\Manager\OAuth1\User;

/**
 * Garmin Connect OAuth 1.0.
 *
 * This class reflects one oddity: Garmin expects the oauth_verifier to be located
 * in the header instead of the post body.
 */
class Server extends BaseServer
{
    /**
     * oauth_verifier stored for use with.
     *
     * @var string
     */
    private $verifier;

    /**
     * {@inheritdoc}
     */
    public function urlTemporaryCredentials()
    {
        return 'https://connectapi.garmin.com/oauth-service/oauth/request_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlAuthorization()
    {
        return 'https://connect.garmin.com/oauthConfirm';
    }

    /**
     * {@inheritdoc}
     */
    public function urlTokenCredentials()
    {
        return 'https://connectapi.garmin.com/oauth-service/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlUserDetails()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        $user = new User;
        $user->id = $data['id'];
        $user->nickname = $data['nickname'];
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->avatar = $data['avatar'];

        $used = ['id', 'nickname', 'name', 'email', 'avatar'];

        foreach ($data as $key => $value) {
            if (! in_array($key, $used, true)) {
                $used[] = $key;
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
        return $data['email'];
    }

    /**
     * {@inheritdoc}
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return $data['screen_name'];
    }

    /**
     * {@inheritdoc}
     */
    protected function additionalProtocolParameters()
    {
        return [
            'oauth_verifier' => $this->verifier,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenCredentials(TemporaryCredentials $temporaryCredentials, $temporaryIdentifier, $verifier)
    {
        $this->verifier = $verifier;

        return parent::getTokenCredentials($temporaryCredentials, $temporaryIdentifier, $verifier);
    }
}
