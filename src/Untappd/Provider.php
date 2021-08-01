<?php

namespace SocialiteProviders\Untappd;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'UNTAPPD';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://untappd.com/oauth/authenticate/',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://untappd.com/oauth/authorize/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.untappd.com/v4/user/info?access_token='.$token,
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return Arr::get(json_decode((string) $response->getBody(), true), 'response.user');
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'              => $user['id'],
            'nickname'        => $user['user_name'],
            'name'            => Arr::get($user, 'first_name').' '.Arr::get($user, 'last_name'),
            'email'           => Arr::get($user, 'settings.email_address'),
            'avatar'          => Arr::get($user, 'user_avatar'),
            'avatar_original' => Arr::get($user, 'user_avatar_hd'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'response_type' => 'code',
        ]);
    }

    /**
     * Get the access token response for the given code.
     *
     * @param string $code
     *
     * @return array
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => ['Accept' => 'application/json'],
            RequestOptions::QUERY   => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }
}
