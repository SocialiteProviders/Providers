<?php

namespace SocialiteProviders\Trello;

use Illuminate\Support\Arr;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Trello;
use SocialiteProviders\Manager\OAuth1\Server as BaseServer;
use SocialiteProviders\Manager\OAuth1\User;

class Server extends BaseServer
{
    /**
     * Access token.
     *
     * @var string
     */
    protected $accessToken;

    /**
     * Application key.
     *
     * @var string
     */
    protected $applicationKey;

    /**
     * Set the access token.
     *
     * @param string $accessToken
     *
     * @return Trello
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function urlTemporaryCredentials()
    {
        return 'https://trello.com/1/OAuthGetRequestToken';
    }

    /**
     * {@inheritdoc}
     */
    public function urlAuthorization()
    {
        return 'https://trello.com/1/OAuthAuthorizeToken?'.
            $this->buildAuthorizationQueryParameters();
    }

    /**
     * {@inheritdoc}
     */
    public function urlTokenCredentials()
    {
        return 'https://trello.com/1/OAuthGetAccessToken';
    }

    /**
     * {@inheritdoc}
     */
    public function urlUserDetails()
    {
        return 'https://trello.com/1/members/me?key='.$this->applicationKey.'&token='.$this->accessToken;
    }

    /**
     * {@inheritdoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        $user = new User();

        $user->nickname = $data['username'];
        $user->name = $data['fullName'];
        $user->imageUrl = null;

        $user->extra = (array) $data;

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

    /**
     * Build authorization query parameters.
     *
     * @return string
     */
    private function buildAuthorizationQueryParameters()
    {
        $scopes = $this->formatScopes($this->scopes, $this->scopeSeparator);

        $params = [
            'response_type' => 'fragment',
            'scope'         => $scopes ?: 'read',
            'expiration'    => Arr::get($this->parameters, 'expiration', '1day'),
            'name'          => Arr::get($this->parameters, 'name'),
        ];

        return http_build_query($params);
    }
}
