<?php

namespace SocialiteProviders\CampaignMonitor;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'CAMPAIGNMONITOR';

    protected $scopes = ['ViewReports'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://api.createsend.com/oauth', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.createsend.com/oauth/token';
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
