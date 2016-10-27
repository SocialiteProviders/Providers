<?php

namespace SocialiteProviders\Steam;

use LightOpenID;
use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider implements ProviderInterface
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
    private function getOpenID()
    {
        $openID = new LightOpenID(
            $redirect = $this->getConfig('redirect')
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

        $openID->identity = 'http://steamcommunity.com/openid';

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
        $endpoint = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=%s&steamids=%s';

        $response = $this->getHttpClient()->get(
            sprintf($endpoint, $this->getConfig('client_secret'), $token)
        );

        $contents = json_decode($response->getBody()->getContents(), true);

        return array_get($contents, 'response.players.0');
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['steamid'],
            'nickname' => array_get($user, 'personaname'),
            'name' => array_get($user, 'realname'),
            'email' => null,
            'avatar' => array_get($user, 'avatarmedium'),
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
}
