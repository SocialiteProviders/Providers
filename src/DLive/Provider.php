<?php

namespace SocialiteProviders\DLive;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'DLIVE';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['identity', 'email:read'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://dlive.tv/o/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://dlive.tv/o/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.dlive.tv?query={me{id username displayname avatar private{email}}}', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => Arr::get($user,'id'),
            'nickname' => Arr::get($user,'displayname'),
            'name'     => Arr::get($user,'username'),
            'email'    => Arr::get($user,'email'),
            'avatar'   => Arr::get($user,'avatar'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code'
        ]);
    }
}
