<?php

namespace SocialiteProviders\Naver;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'NAVER';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://nid.naver.com/oauth2.0/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
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
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     *
     * @see https://developers.naver.com/docs/login/profile/
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'        => Arr::get($user, 'response.id'),
            'name'      => Arr::get($user, 'response.name'),
            'nickname'  => Arr::get($user, 'response.nickname'),
            'email'     => Arr::get($user, 'response.email'),
            'avatar'    => Arr::get($user, 'response.profile_image'),
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
