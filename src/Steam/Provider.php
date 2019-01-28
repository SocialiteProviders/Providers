<?php

namespace SocialiteProviders\Steam;

use Illuminate\Support\Arr;
use LightOpenID;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'STEAM';

    /**
     * {@inheritdoc}
     */
    protected $stateless = true;

    /**
     * Returns the Open ID object.
     *
     * @return \LightOpenID
     */
    protected function getOpenID()
    {
        $openID = new LightOpenID(
            $redirect = $this->redirectUrl,
            $this->getConfig('proxy')
        );

        $openID->returnUrl = $redirect;

        return $openID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $openID = $this->getOpenID();

        $openID->identity = 'https://steamcommunity.com/openid';

        return $openID->authUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        $openID = $this->getOpenID();

        if (!$openID->validate()) {
            throw new OpenIDValidationException();
        }

        $user = $this->mapUserToObject($this->getUserByToken(
            $this->parseAccessToken($openID->identity)
        ));

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessToken($body)
    {
        preg_match('/\/id\/(\d+)$/i', $body, $matches);

        return $matches[1];
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            sprintf('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=%s&steamids=%s', $this->clientSecret, $token)
        );

        $contents = json_decode($response->getBody()->getContents(), true);

        return Arr::get($contents, 'response.players.0');
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['steamid'],
            'nickname' => Arr::get($user, 'personaname'),
            'name'     => Arr::get($user, 'realname'),
            'email'    => null,
            'avatar'   => Arr::get($user, 'avatarmedium'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
    }

    public static function additionalConfigKeys()
    {
        return ['proxy'];
    }
}
