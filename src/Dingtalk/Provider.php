<?php

namespace SocialiteProviders\Yahoo;

use Illuminate\Support\Arr;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'DINGTALK';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://oapi.dingtalk.com/connect/oauth2/sns_authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://oapi.dingtalk.com/sns/gettoken';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://oapi.dingtalk.com/sns/getuserinfo', [
            'query' => [
                'sns_token' => $token,
            ],
        ]);

        return json_decode($response->getBody(), true)['user_info'];
    }

    /**
     * Maps Yahoo object to User Object.
     *
     * Note: To have access to e-mail, you need to request "Profiles (Social Directory) - Read/Write Public and Private"
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['openid'],
            'nickname' => $user['nick'],
            'name'     => '',
            'email'    => '',
            'avatar'   => '',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessToken($body)
    {
        return Arr::get($body, 'access_token');
    }

}