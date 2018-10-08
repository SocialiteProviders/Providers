<?php

namespace SocialiteProviders\Deezer;

use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'DEEZER';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['basic_access', 'email'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://connect.deezer.com/oauth/auth.php',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://connect.deezer.com/oauth/access_token.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.deezer.com/user/me?access_token=' . $token
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'],
            'email' => $user['email'],
            'nickname' => $user['name'],
            'avatar' => $user['picture'],
            'name' => $user['firstname'] . ' ' . $user['lastname'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        return [
            'state' => $state,
            'response_type' => 'code',
            'app_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'scope' => $this->formatScopes($this->scopes, $this->scopeSeparator),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $url = $this->getTokenUrl() . '?' . http_build_query(
            $this->getTokenFields($code),
            '',
            '&',
            $this->encodingType
        );

        $response = $this->getHttpClient()->get($url);

        parse_str($response->getBody()->getContents(), $data);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return [
            'code' => $code,
            'app_id' => $this->clientId,
            'secret' => $this->clientSecret,
        ];
    }
}
