<?php

namespace SocialiteProviders\Graph;

use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'GRAPH';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['openid mail.send User.ReadBasic.All'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://login.microsoftonline.com/common/oauth2/v2.0/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://graph.microsoft.com/v1.0/me/', [
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
        // Mapping all response data plus default Laravel user keys.
        return (new User())->setRaw($user)->map([
            'id'                => $user['id'],
            'businessPhones'    => $user['businessPhones'],
            'displayName'       => $user['displayName'],
            'name'              => $user['displayName'],
            'givenName'         => $user['givenName'],
            'jobTitle'          => $user['jobTitle'],
            'email'             => $user['mail'],
            'mail'              => $user['mail'],
            'mobilePhone'       => $user['mobilePhone'],
            'officeLocation'    => $user['officeLocation'],
            'preferredLanguage' => $user['preferredLanguage'],
            'surname'           => $user['surname'],
            'userPrincipalName' => $user['userPrincipalName'],
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
