<?php

namespace SocialiteProviders\Smashcast;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'SMASHCAST';

    /**
     * {@inheritdoc}
     */
    protected $stateless = true;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://api.smashcast.tv/oauth/login', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.smashcast.tv/oauth/exchange';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $username = $this->getUserNameByToken($token);

        $response = $this->getHttpClient()->get('https://api.smashcast.tv/user/'.$username, [
            RequestOptions::QUERY => ['authToken' => $token],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function getUserNameByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.smashcast.tv/userfromtoken/'.$token);

        return Arr::get(json_decode((string) $response->getBody(), true), 'user_name');
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => Arr::get($user, 'user_id'),
            'nickname' => Arr::get($user, 'user_name'),
            'email'    => Arr::get($user, 'user_email'),
            'avatar'   => Arr::get($user, 'user_logo'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        return ['app_token' => $this->clientId];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCode()
    {
        return $this->request->input('request_token');
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return [
            'request_token' => $code,
            'app_token'     => $this->clientId,
            'hash'          => base64_encode($this->clientId.$this->clientSecret),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        $user = $this->mapUserToObject($this->getUserByToken(
            $token = $this->getAccessToken() ?: Arr::get($this->getAccessTokenResponse($this->getCode()), 'access_token')
        ));

        return $user->setToken($token);
    }

    protected function getAccessToken()
    {
        return $this->request->input('authToken');
    }
}
