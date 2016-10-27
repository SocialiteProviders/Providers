<?php

namespace SocialiteProviders\Weibo;

use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'WEIBO';

    /**
     * {@inheritdoc}.
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://api.weibo.com/oauth2/authorize', $state);
    }

    /**
     * {@inheritdoc}.
     */
    protected function getTokenUrl()
    {
        return 'https://api.weibo.com/oauth2/access_token';
    }

    /**
     * {@inheritdoc}.
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.weibo.com/2/users/show.json', [
            'query' => [
                'access_token' => $token,
                'uid' => $this->getUid($token),
            ],
        ]);

        return json_decode($this->removeCallback($response->getBody()->getContents()), true);
    }

    /**
     * {@inheritdoc}.
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['idstr'], 'nickname' => $user['name'],
            'avatar' => $user['avatar_large'], 'name' => null, 'email' => null,
        ]);
    }

    /**
     * {@inheritdoc}.
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * {@inheritdoc}.
     */
    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'query' => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode($response->getBody(), true);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * @param mixed $response
     *
     * @return string
     */
    protected function removeCallback($response)
    {
        if (strpos($response, 'callback') !== false) {
            $lpos = strpos($response, '(');
            $rpos = strrpos($response, ')');
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
        }

        return $response;
    }

    /**
     * @param $token
     *
     * @return string
     */
    protected function getUid($token)
    {
        $response = $this->getHttpClient()->get('https://api.weibo.com/2/account/get_uid.json', [
            'query' => ['access_token' => $token],
        ]);

        return json_decode($response->getBody(), true)['uid'];
    }
}
