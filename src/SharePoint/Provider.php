<?php

namespace SocialiteProviders\SharePoint;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'SHAREPOINT';

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['site_url'];
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getConfig('site_url').'/_layouts/15/OAuthAuthorize.aspx', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://accounts.accesscontrol.windows.net/72ada2a1-5d29-4eed-b194-f8745777149e/tokens/OAuth/2';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['username'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => $user['avatar'],
        ]);
    }
}
