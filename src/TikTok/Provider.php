<?php

namespace SocialiteProviders\TikTok;

use Illuminate\Support\Arr;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
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
        return 'https://open-api.tiktok.com/platform/oauth/connect?'.http_build_query([
            'client_key'    => $this->clientId,
            'state'         => $state,
            'response_type' => 'code',
            'scope'         => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'redirect_uri'  => $this->redirectUrl,
        ]);
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

        $token = Arr::get($response, 'data.access_token');

        $this->user = $this->mapUserToObject(
            $this->getUserByToken([
                'access_token' => $token,
                'open_id'      => Arr::get($response, 'data.open_id'),
            ])
        );

        return $this->user->setToken($token)
            ->setExpiresIn(Arr::get($response, 'data.expires_in'))
            ->setRefreshToken(Arr::get($response, 'data.refresh_token'))
            ->setApprovedScopes(explode($this->scopeSeparator, Arr::get($response, 'data.scope', '')));
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenUrl()
    {
        return 'https://open-api.tiktok.com/oauth/access_token/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return [
            'client_key'    => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
        ];
    }

    /**
     * Get TikTok user by token.
     *
     * @param array $data
     *
     * @return mixed
     */
    protected function getUserByToken($data)
    {
        // Note: The TikTok api does not have an endpoint to get a user by the access
        // token only. Open id is also required therefore:
        // $data['access_token'] = $token, $data['open_id'] = $open_id

        $response = $this->getHttpClient()->get(
            'https://open-api.tiktok.com/oauth/userinfo?'.http_build_query($data)
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject($user)
    {
        $user = $user['data'];

        return (new User())->setRaw($user)->map([
            'id'       => $user['open_id'],
            'union_id' => $user['union_id'],
            'name'     => $user['display_name'],
            'avatar'   => $user['avatar_larger'],
        ]);
    }
}
