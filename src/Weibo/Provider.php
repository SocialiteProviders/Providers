<?php

namespace SocialiteProviders\Weibo;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'WEIBO';

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
            RequestOptions::QUERY => [
                'access_token' => $token,
                'uid'          => $this->getUid($token),
            ],
        ]);

        return json_decode($this->removeCallback((string) $response->getBody()), true);
    }

    /**
     * {@inheritdoc}.
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'     => $user['idstr'], 'nickname' => $user['name'],
            'avatar' => $user['avatar_large'], 'name' => null, 'email' => null,
        ]);
    }

    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::QUERY => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode((string) $response->getBody(), true);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * @param  mixed  $response
     * @return string
     */
    protected function removeCallback($response)
    {
        if (str_contains($response, 'callback')) {
            $lpos = strpos($response, '(');
            $rpos = strrpos($response, ')');
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
        }

        return $response;
    }

    protected function getUid(string $token): string
    {
        $response = $this->getHttpClient()->get('https://api.weibo.com/2/account/get_uid.json', [
            RequestOptions::QUERY => ['access_token' => $token],
        ]);

        return json_decode((string) $response->getBody(), true)['uid'];
    }
}
