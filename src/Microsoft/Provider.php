<?php

namespace SocialiteProviders\Microsoft;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Microsoft\MicrosoftUser as User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'MICROSOFT';

    /**
     * Default field list to request from Microsoft.
     *
     * @see https://docs.microsoft.com/en-us/graph/permissions-reference#user-permissions
     */
    protected const DEFAULT_FIELDS_USER = ['id', 'displayName', 'businessPhones', 'givenName', 'jobTitle', 'mail', 'mobilePhone', 'officeLocation', 'preferredLanguage', 'surname', 'userPrincipalName'];

    /**
     * Default tenant field list to request from Microsoft.
     *
     * @see https://docs.microsoft.com/en-us/graph/permissions-reference#user-permissions
     */
    protected const DEFAULT_FIELDS_TENANT = ['id', 'displayName', 'city', 'country', 'countryLetterCode', 'state', 'street', 'verifiedDomains'];
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
                    $this->getConfig('tenant', 'common')
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
        $responseUser = $this->getHttpClient()->get(
            'https://graph.microsoft.com/v1.0/me',
            [
                RequestOptions::HEADERS => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
                RequestOptions::QUERY => [
                    '$select' => implode(',', array_merge(self::DEFAULT_FIELDS_USER, $this->getConfig('fields', []))),
                ],
            ]
        );

        $formattedResponse = json_decode((string) $responseUser->getBody(), true);

        if ($this->getConfig('tenant', 'common') === 'common' && $this->getConfig('include_tenant_info', false)) {
            $responseTenant = $this->getHttpClient()->get(
                'https://graph.microsoft.com/v1.0/organization',
                [
                    RequestOptions::HEADERS => [
                        'Accept'        => 'application/json',
                        'Authorization' => 'Bearer '.$token,
                    ],
                    RequestOptions::QUERY => [
                        '$select' => implode(',', array_merge(self::DEFAULT_FIELDS_TENANT, $this->getConfig('tenant_fields', []))),
                    ],
                ]
            );

            $formattedResponse['tenant'] = json_decode((string) $responseTenant->getBody(), true)['value'][0] ?? null;
        }

        return $formattedResponse;
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

            'businessPhones'    => Arr::get($user, 'businessPhones'),
            'displayName'       => Arr::get($user, 'displayName'),
            'givenName'         => Arr::get($user, 'givenName'),
            'jobTitle'          => Arr::get($user, 'jobTitle'),
            'mail'              => Arr::get($user, 'mail'),
            'mobilePhone'       => Arr::get($user, 'mobilePhone'),
            'officeLocation'    => Arr::get($user, 'officeLocation'),
            'preferredLanguage' => Arr::get($user, 'preferredLanguage'),
            'surname'           => Arr::get($user, 'surname'),
            'userPrincipalName' => Arr::get($user, 'userPrincipalName'),

            'tenant' => Arr::get($user, 'tenant'),
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
     * Add the additional configuration key 'tenant' to enable the branded sign-in experience,
     * and the key 'fields' to request extra fields from the Microsoft Graph.
     *
     * @return array
     */
    public static function additionalConfigKeys()
    {
        return ['tenant', 'include_tenant_info', 'fields', 'tenant_fields'];
    }
}
