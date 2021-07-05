<?php

namespace SocialiteProviders\Facebook;

use Illuminate\Support\Arr;

use SocialiteProviders\Manager\OAuth2\User;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

use Laravel\Socialite\Two\InvalidStateException;

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
        'user.info.basic'
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return "https://open-api.tiktok.com/platform/oauth/connect?" . http_build_query([
            "client_key" => $this->clientId,
            "state" => $state,
            "response_type" => "code",
            "scope" => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            "redirect_uri" => $this->redirectUrl,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if($this->user) { return $this->user; }

        if($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        $token = Arr::get($response, "data.access_token");

        $this->user = $this->mapUserToObject(
            $this->getUserByToken([$token, Arr::get($response, "data.open_id")])
        );

        return $this->user->setToken($token)
            ->setExpiresIn(Arr::get($response, "data.expires_in"))
            ->setRefreshToken(Arr::get($response, "data.refresh_token"));
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        $fields = [
            "client_key" => $this->clientId,
            "client_secret" => $this->clientSecret,
            "code" => $code,
            "grant_type" => "authorization_code",
        ];

        return $fields;
    }

    /**
     * Get TikTok user by token.
     * 
     * @param array $data
     * @return mixed
     */
    protected function getUserByToken($data)
    {
        // Note: The TikTok api does not currently have a way to get the user data
        // with only the access token.

        $response = $this->getHttpClient()->get(
            "https://open-api.tiktok.com/oauth/userinfo?" . http_build_query([
                "open_id" => $data[1],
                "access_token" => $data[0]
            ])
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject($user)
    {
        $user = $user['data'];

        return (new User())->setRaw($user)->map([
            'id' => $user['open_id'],
            'avatar' => $user['avatar_larger'],
            'name' => $user['display_name'],
            'union_id' => $user['union_id'],
        ]);
    }
}
