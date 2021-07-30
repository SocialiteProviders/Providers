<?php

namespace SocialiteProviders\Meetup;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'MEETUP';

    protected $version = '2';
    protected $scopes = ['ageless'];
    protected $scopeSeparator = '+';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return urldecode($this->buildAuthUrlFromBase('https://secure.meetup.com/oauth2/authorize', $state));
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
        // https://www.meetup.com/meetup_api/auth/#oauth2-resources
        $response = $this->getHttpClient()->get(
            'https://api.meetup.com/'.$this->version.'/member/self?access_token='.$token,
            [
                RequestOptions::HEADERS =>  [
                    'Accept' => 'application/json',
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'   => $user['id'], 'nickname' => $user['name'],
            'name' => $user['name'], 'avatar' => Arr::get($user, 'photo.photo_link'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        // see https://www.meetup.com/meetup_api/auth/#oauth2server-access
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
