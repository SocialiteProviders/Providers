<?php

namespace SocialiteProviders\TikTok;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://developers.tiktok.com/bulletin/migration-guidance-oauth-v1/
 * @see https://developers.tiktok.com/doc/oauth-user-access-token-management
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'TIKTOK';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'user.info.basic',
    ];

    /**
     * @var User
     */
    protected $user;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $fields = [
            'client_key'    => $this->clientId,
            'state'         => $state,
            'response_type' => 'code',
            'scope'         => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'redirect_uri'  => $this->redirectUrl,
        ];

        $fields = array_merge($fields, $this->parameters);

        return 'https://www.tiktok.com/v2/auth/authorize/?'.http_build_query($fields);
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->user) {
            return $this->user;
        }

        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        $token = Arr::get($response, 'access_token');

        $this->user = $this->mapUserToObject(
            $this->getUserByToken($token)
        );

        return $this->user->setToken($token)
            ->setExpiresIn(Arr::get($response, 'expires_in'))
            ->setRefreshToken(Arr::get($response, 'refresh_token'))
            ->setApprovedScopes(explode($this->scopeSeparator, Arr::get($response, 'scope', '')));
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenUrl()
    {
        return 'https://open.tiktokapis.com/v2/oauth/token/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        $fields = parent::getTokenFields($code);
        $fields['client_key'] = $this->clientId;
        unset($fields['client_id']);

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://open.tiktokapis.com/v2/user/info/', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
            RequestOptions::QUERY => [
                'fields' => 'open_id,union_id,display_name,avatar_large_url,username',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject($user)
    {
        $user = $user['data']['user'];

        return (new User())->setRaw($user)->map([
            'id'       => $user['open_id'],
            'nickname' => $user['username'] ?? null,
            'union_id' => $user['union_id'] ?? null,
            'name'     => $user['display_name'],
            'avatar'   => $user['avatar_large_url'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenHeaders($code)
    {
        return [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
    }
}
