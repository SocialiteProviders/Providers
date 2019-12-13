<?php

namespace SocialiteProviders\JohnDeere;

use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use SocialiteProviders\Manager\OAuth1\Server as BaseServer;
use SocialiteProviders\Manager\OAuth1\User;

class Server extends BaseServer
{
    const SANDBOX_URL = 'https://sandboxapi.deere.com';
    const PRODUCTION_URL = '';

    /**
     * oauth_verifier stored for use with.
     *
     * @var string
     */
    protected $verifier;

    /**
     * Determine if we are currently in sandbox environment based on the service config
     *
     * @return boolean
     */
    protected function useSandbox()
    {
        return config('services.john-deere.env', 'sandbox') !== 'production';
    }

    /**
     * Get the base URL between sandbox or production
     * 
     * @return string
     */
    protected function baseUrl()
    {
        return $this->useSandbox() ? self::SANDBOX_URL : self::PRODUCTION_URL;
    }

    /**
     * @inheritDoc
     */
    public function urlTemporaryCredentials()
    {
        return $this->baseUrl().'platform/oauth/request_token';
    }

    /**
     * @inheritDoc
     */
    public function urlAuthorization()
    {
        return 'https://my.deere.com/consentToUseOfData';
    }

    /**
     * @inheritDoc
     */
    public function urlTokenCredentials()
    {
        return $this->baseUrl().'platform/oauth/access_token';
    }

    /**
     * @inheritDoc
     */
    public function urlUserDetails()
    {
        return $this->baseUrl().'platform/users/@currentUser';
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenCredentials(TemporaryCredentials $temporaryCredentials, $temporaryIdentifier, $verifier)
    {
        $this->verifier = $verifier;

        return parent::getTokenCredentials($temporaryCredentials, $temporaryIdentifier, $verifier);
    }

    /**
     * @inheritDoc
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        $user = new User();
        $user->id = $data['accountName'];
        $user->name = "{$data['givenName']} {$data['familyName']}";

        $used = ['accountName', 'givenName', 'familyName'];

        $user->extra = array_diff_key($data, array_flip($used));

        return $user;
    }

    /**
     * @inheritDoc
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return $data['accountName'];
    }

    /**
     * @inheritDoc
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return $data['accountName'];
    }

    /**
     * {@inheritdoc}
     */
    protected function additionalProtocolParameters()
    {
        return ['oauth_verifier' => $this->verifier];
    }

    /**
     * {@inheritdoc}
     */
    protected function buildHttpClientHeaders($headers = array())
    {
        return array_merge(
            parent::buildHttpClientHeaders($headers),
            ['Accept' => 'application/vnd.deere.axiom.v3+json']
        );
    }
}
