<?php

namespace SocialiteProviders\Meetup;

use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'MEETUP';

    protected $version = '2';
    protected $scopes = ['ageless'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://secure.meetup.com/oauth2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://secure.meetup.com/oauth2/access';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        // http://www.meetup.com/meetup_api/auth/#oauth2-resources
        $response = $this->getHttpClient()->get(
            'https://api.meetup.com/'.$this->version.'/member/self?access_token='.$token, [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'], 'nickname' => $user['name'],
            'name' => $user['name'], 'avatar' => array_get($user, 'photo.photo_link'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        // see http://www.meetup.com/meetup_api/auth/#oauth2server-access
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
