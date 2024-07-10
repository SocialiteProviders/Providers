<?php

namespace SocialiteProviders\Microsoft;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
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
    protected const DEFAULT_FIELDS_USER = [
        'id',
        'displayName',
        'businessPhones',
        'givenName',
        'jobTitle',
        'department',
        'mail',
        'mobilePhone',
        'officeLocation',
        'preferredLanguage',
        'surname',
        'userPrincipalName',
        'employeeId',
    ];

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
        return sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', $this->getConfig('tenant', 'common'));
    }

    /**
     * Return the logout endpoint with an optional post_logout_redirect_uri query parameter.
     *
     * @param string|null $redirectUri The URI to redirect to after logout, if provided.
     *                                    If not provided, no post_logout_redirect_uri parameter will be included.
     *
     * @return string The logout endpoint URL.
     */
    public function getLogoutUrl(?string $redirectUri = null)
    {
        $logoutUrl = sprintf('https://login.microsoftonline.com/%s/oauth2/logout', $this->getConfig('tenant', 'common'));

        return $redirectUri === null ?
            $logoutUrl :
            $logoutUrl . '?' . http_build_query(['post_logout_redirect_uri' => $redirectUri], '', '&', $this->encodingType);
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
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                RequestOptions::QUERY => [
                    '$select' => implode(',', array_merge(self::DEFAULT_FIELDS_USER, $this->getConfig('fields', []))),
                ],
                RequestOptions::PROXY => $this->getConfig('proxy'),
            ]
        );

        $formattedResponse = json_decode((string) $responseUser->getBody(), true);

        if ($this->getConfig('include_avatar', false)) {
            try {
                $imageSize = $this->getConfig('include_avatar_size', '648x648');
                $responseAvatar = $this->getHttpClient()->get(
                    "https://graph.microsoft.com/v1.0/me/photos/{$imageSize}/\$value",
                    [
                        RequestOptions::HEADERS => [
                            'Accept' => 'image/jpg',
                            'Authorization' => 'Bearer ' . $token,
                        ],
                        RequestOptions::PROXY => $this->getConfig('proxy'),
                    ]
                );

                $formattedResponse['avatar'] = base64_encode($responseAvatar->getBody()->getContents()) ?? null;
            } catch (ClientException) {
                //if exception then avatar does not exist.
                $formattedResponse['avatar'] = null;
            }
        }

        if ($this->getConfig('include_tenant_info', false)) {
            try {
                $responseTenant = $this->getHttpClient()->get(
                    'https://graph.microsoft.com/v1.0/organization',
                    [
                        RequestOptions::HEADERS => [
                            'Accept' => 'application/json',
                            'Authorization' => 'Bearer ' . $token,
                        ],
                        RequestOptions::QUERY => [
                            '$select' => implode(',', array_merge(self::DEFAULT_FIELDS_TENANT, $this->getConfig('tenant_fields', []))),
                        ],
                        RequestOptions::PROXY => $this->getConfig('proxy'),
                    ]
                );
                $formattedResponse['tenant'] = json_decode((string) $responseTenant->getBody(), true)['value'][0] ?? null;
            } catch (RequestException) {
                //if exception then tenant does not exist.
                $formattedResponse['tenant'] = null;
            }
        }

        return $formattedResponse;
    }

    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => ['Accept' => 'application/json'],
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
            RequestOptions::PROXY => $this->getConfig('proxy'),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'],
            'nickname' => null,
            'name' => $user['displayName'],
            'email' => $user['userPrincipalName'],
            'avatar' => Arr::get($user, 'avatar'),

            'businessPhones' => Arr::get($user, 'businessPhones'),
            'displayName' => Arr::get($user, 'displayName'),
            'givenName' => Arr::get($user, 'givenName'),
            'jobTitle' => Arr::get($user, 'jobTitle'),
            'department' => Arr::get($user, 'department'),
            'mail' => Arr::get($user, 'mail'),
            'mobilePhone' => Arr::get($user, 'mobilePhone'),
            'officeLocation' => Arr::get($user, 'officeLocation'),
            'preferredLanguage' => Arr::get($user, 'preferredLanguage'),
            'surname' => Arr::get($user, 'surname'),
            'userPrincipalName' => Arr::get($user, 'userPrincipalName'),
            'employeeId' => Arr::get($user, 'employeeId'),

            'tenant' => Arr::get($user, 'tenant'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'scope' => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
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
        return [
            'tenant',
            'include_tenant_info',
            'include_avatar',
            'include_avatar_size',
            'fields',
            'tenant_fields',
            'proxy',
        ];
    }
}
