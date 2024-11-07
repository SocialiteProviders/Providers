<?php

namespace SocialiteProviders\Paymill;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'PAYMILL';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['transactions_rw'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://connect.paymill.com/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://connect.paymill.com/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user);
    }
}
