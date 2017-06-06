<?php

namespace SocialiteProviders\QuickBooks;

use SocialiteProviders\Manager\OAuth1\User;
use League\OAuth1\Client\Credentials\TokenCredentials;
use SocialiteProviders\Manager\OAuth1\Server as BaseServer;

class Server extends BaseServer
{
    /**
     * The response type for data returned from API calls.
     *
     * @var string
     */
    protected $responseType = 'xml';

    /**
     * {@inheritdoc}
     */
    public function urlTemporaryCredentials()
    {
        return 'https://oauth.intuit.com/oauth/v1/get_request_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlAuthorization()
    {
        return 'https://appcenter.intuit.com/Connect/Begin';
    }

    /**
     * {@inheritdoc}
     */
    public function urlTokenCredentials()
    {
        return 'https://oauth.intuit.com/oauth/v1/get_access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlUserDetails()
    {
        return 'https://appcenter.intuit.com/api/v1/user/current';
    }

    /**
     * {@inheritdoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        $user = new User();
        $user->id = $this->userUid($data, $tokenCredentials);
        $user->name = $this->userScreenName($data, $tokenCredentials);
        $user->email = $this->userEmail($data, $tokenCredentials);
        $user->verified = (bool)$data->User->IsVerified;

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return (string) $data->User['Id'];
    }

    /**
     * {@inheritdoc}
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return (string) $data->User->EmailAddress;
    }

    /**
     * {@inheritdoc}
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return (string) $data->User->ScreenName;
    }
}
