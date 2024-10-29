<?php

namespace SocialiteProviders\Naver;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'NAVER';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://nid.naver.com/oauth2.0/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://nid.naver.com/oauth2.0/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://openapi.naver.com/v1/nid/me',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     *
     * @see https://developers.naver.com/docs/login/profile/
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'        => Arr::get($user, 'response.id'),
            'name'      => Arr::get($user, 'response.name'),
            'nickname'  => Arr::get($user, 'response.nickname'),
            'email'     => Arr::get($user, 'response.email'),
            'avatar'    => Arr::get($user, 'response.profile_image'),
        ]);
    }
}
