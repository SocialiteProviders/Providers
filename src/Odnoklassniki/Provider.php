<?php

namespace SocialiteProviders\Odnoklassniki;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
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
        $secretKey = md5($token.$this->clientSecret);

        $publicKey = app()['config']['services.odnoklassniki']['client_public'];

        $sign = 'application_key='.$publicKey.'format=jsonmethod=users.getCurrentUser'.$secretKey;

        $response = $this->getHttpClient()->get('https://api.odnoklassniki.ru/fb.do', [
            RequestOptions::QUERY => [
                'method'          => 'users.getCurrentUser',
                'format'          => 'json',
                'application_key' => $publicKey,
                'sig'             => md5($sign),
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
}
