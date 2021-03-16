<?php

namespace SocialiteProviders\Microsoft;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Microsoft\MicrosoftUser as User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'MICROSOFT';

    /**
     * {@inheritdoc}
     * https://msdn.microsoft.com/en-us/library/azure/ad/graph/howto/azure-ad-graph-api-permission-scopes.
     */
    protected $scopes = ['User.Read'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return
            $this->buildAuthUrlFromBase(
                sprintf(
                    'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize',
                    $this->config['tenant'] ?: 'common'
                ),
                $state
            );
    }

    /**
     * {@inheritdoc}
     * https://developer.microsoft.com/en-us/graph/docs/concepts/use_the_api.
     */
    protected function getTokenUrl()
    {
        return sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', $this->config['tenant'] ?: 'common');
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://graph.microsoft.com/v1.0/me',
            [
                'headers' => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => null,
            'name'     => $user['displayName'],
            'email'    => $user['userPrincipalName'],
            'avatar'   => null,

            'businessPhones'    => $user['businessPhones'],
            'displayName'       => $user['displayName'],
            'givenName'         => $user['givenName'],
            'jobTitle'          => $user['jobTitle'],
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
            'grant_type' => 'authorization_code',
            'scope'      => parent::formatScopes(parent::getScopes(), $this->scopeSeparator),
        ]);
    }

    /**
     * Add the additional configuration key 'tenant' to enable the branded sign-in experience.
     *
     * @return array
     */
    public static function additionalConfigKeys()
    {
        return ['tenant'];
    }
}
