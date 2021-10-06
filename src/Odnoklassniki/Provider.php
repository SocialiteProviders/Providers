<?php

namespace SocialiteProviders\Odnoklassniki;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'ODNOKLASSNIKI';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['VALUABLE_ACCESS', 'GET_EMAIL'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ';';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://connect.ok.ru/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.ok.ru/oauth/token.do';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $sig = 'application_key='.$this->getConfig('client_public').'format=jsonmethod=users.getCurrentUser';
        $sig .= md5($token.$this->clientSecret);

        $response = $this->getHttpClient()->get('https://api.odnoklassniki.ru/fb.do', [
            RequestOptions::QUERY => [
                'method'          => 'users.getCurrentUser',
                'format'          => 'json',
                'application_key' => $this->getConfig('client_public'),
                'sig'             => md5($sig),
                'access_token'    => $token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => Arr::get($user, 'uid'),
            'nickname' => null,
            'name'     => Arr::get($user, 'name'),
            'email'    => Arr::get($user, 'email'),
            'avatar'   => Arr::get($user, 'pic_3'),
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
}
