<?php

namespace SocialiteProviders\Ovh;

use Ovh\Api as Ovh;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * Class Provider.
 */
class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'OVH';

    /**
     * @return array
     */
    public static function additionalConfigKeys()
    {
        return [
            'endpoint',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $ovh = new Ovh(
            $this->getConfig('client_id'),
            $this->getConfig('client_secret'),
            $this->getConfig('endpoint')
        );

        $request = $ovh->requestCredentials(
            [
                [
                    'method'    => 'GET',
                    'path'      => '/me',
                ],
            ],
            $this->getConfig('redirect').'?state='.$state
        );

        app()['session']->flash($state, $request['consumerKey']);

        return $request['validationUrl'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCode()
    {
        return $this->request->input('state');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($state)
    {
        return [
            'access_token' => app()['session']->get($state),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $ovh = new Ovh(
            $this->getConfig('client_id'),
            $this->getConfig('client_secret'),
            $this->getConfig('endpoint'),
            $token
        );

        return $ovh->get('/me');
    }

    /**
     * {@inheritdoc}
     */
    protected function hasInvalidState()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map(
            [
                'avatar'   => null,
                'email'    => $user['email'],
                'id'       => $user['customerCode'],
                'name'     => $user['firstname'].' '.$user['name'],
                'nickname' => $user['nichandle'],
            ]
        );
    }
}
