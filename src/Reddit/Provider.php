<?php

namespace SocialiteProviders\Reddit;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'REDDIT';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['identity'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://ssl.reddit.com/api/v1/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://ssl.reddit.com/api/v1/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://oauth.reddit.com/api/v1/me',
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'User-Agent'    => $this->getUserAgent(),
                ],
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $avatar = null;
        if (!empty($user['icon_img'])) {
            $avatar = $user['icon_img'];

            // Strip the query segment of the URL if it exists.
            // It provides resize attributes that we're not interested in.
            if ($querypos = strpos($avatar, '?')) {
                $avatar = substr($avatar, 0, $querypos);
            }
        }

        $name = null;
        //Check if user has a display name
        if (!empty($user['subreddit']['title'])) {
            $name = $user['subreddit']['title'];
        }

        return (new User())->setRaw($user)->map([
            'id'   => $user['id'], 'nickname' => $user['name'],
            'name' => $name, 'email' => null, 'avatar' => $avatar,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => [
                'Accept'     => 'application/json',
                'User-Agent' => $this->getUserAgent(),
            ],
            'auth'        => [$this->clientId, $this->clientSecret],
            'form_params' => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode($response->getBody(), true);

        return $this->credentialsResponseBody;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return [
            'grant_type'   => 'authorization_code', 'code' => $code,
            'redirect_uri' => $this->redirectUrl,
        ];
    }

    protected function getUserAgent()
    {
        return implode(':', [
            $this->getConfig('platform'),
            $this->getConfig('app_id'),
            $this->getConfig('version_string'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['platform', 'app_id', 'version_string'];
    }
}
