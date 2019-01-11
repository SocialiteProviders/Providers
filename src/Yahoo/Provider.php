<?php

namespace SocialiteProviders\Yahoo;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'YAHOO';

    /**
     * @var string
     */
    protected $xoauth_yahoo_guid;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://api.login.yahoo.com/oauth2/request_auth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.login.yahoo.com/oauth2/get_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://social.yahooapis.com/v1/user/'.$this->xoauth_yahoo_guid.'/profile?format=json', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody(), true)['profile'];
    }

    /**
     * Maps Yahoo object to User Object.
     *
     * Note: To have access to e-mail, you need to request "Profiles (Social Directory) - Read/Write Public and Private"
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['guid'],
            'nickname' => $user['nickname'],
            'name'     => trim(sprintf('%s %s', Arr::get($user, 'givenName'), Arr::get($user, 'familyName'))),
            'email'    => Arr::get($user, 'emails.0.handle'),
            'avatar'   => Arr::get($user, 'image.imageUrl'),
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

    /**
     * {@inheritdoc}
     */
    protected function parseAccessToken($body)
    {
        $this->xoauth_yahoo_guid = Arr::get($body, 'xoauth_yahoo_guid');

        return Arr::get($body, 'access_token');
    }
}
