<?php

namespace SocialiteProviders\Google;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'GOOGLE';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'profile',
        'email',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://accounts.google.com/o/oauth2/auth', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://accounts.google.com/o/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://people.googleapis.com/v1/people/me', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
            'query'   => ['personFields' => 'emailAddresses,names,photos'],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['names'][0]['metadata']['source']['id'],
            'nickname' => Arr::get($user, 'names.0.displayName', NULL),
            'name' => Arr::get($user, 'names.0.displayName', NULL),
            'email' => Arr::get($user, 'emailAddresses.0.value', NULL),
            'avatar' =>  ( 
                    Arr::get($user, 'photos.0.metadata.source.type', NULL) === 'PROFILE'
                    AND Arr::get($user, 'photos.0.metadata.primary', NULL) === true
                ) 
                ? Arr::get($user, 'photos.0.url', NULL) 
                : NULL,
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
