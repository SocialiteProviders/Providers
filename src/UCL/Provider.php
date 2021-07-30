<?php

namespace SocialiteProviders\UCL;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'UCL';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://uclapi.com/oauth/authorise/', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://uclapi.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->hasInvalidState() || !$this->getCode()) {
            throw new InvalidStateException();
        }
        $response = $this->getAccessTokenResponse($this->getCode());
        $user = $this->mapUserToObject($this->getUserByToken(
            $token = Arr::get($response, 'token')
        ));

        return $user->setToken($token);
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param string $code
     *
     * @return array
     */
    protected function getTokenFields($code)
    {
        return [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code'          => $code,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://uclapi.com/oauth/user/data', [RequestOptions::QUERY =>  [
            'client_secret' => $this->clientSecret,
            'token'         => $token,
        ]]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => Arr::get($user, 'upi'),
            'name'     => Arr::get($user, 'full_name'),
            'nickname' => Arr::get($user, 'given_name'),
            'email'    => Arr::get($user, 'email'),
        ]);
    }
}
