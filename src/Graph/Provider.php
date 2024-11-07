<?php

namespace SocialiteProviders\Graph;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'GRAPH';

    protected $scopes = ['User.Read'];

    protected $scopeSeparator = ' ';

    /**
     * Allows you to override the tenant id that the provider is configured
     * with.
     *
     * @param  string  $tenantId
     * @return \SocialiteProviders\Graph\Provider
     */
    public function setTenantId($tenantId)
    {
        $this->config = array_merge($this->config, [
            'tenant_id' => $tenantId,
        ]);

        return $this;
    }

    /**
     * Returns the configured tenant that we're authenticating with, or common
     * if one is not configured.
     *
     * @return string
     */
    private function getTenantId()
    {
        return $this->getConfig('tenant_id', 'common');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            sprintf(
                'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize',
                $this->getTenantId()
            ),
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/token',
            $this->getTenantId()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $userEndpointVersion = $this->getConfig('user_endpoint_version', 'v1.0');
        $response = $this->getHttpClient()->get("https://graph.microsoft.com/$userEndpointVersion/me/", [
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
        // Mapping default Laravel user keys to the keys that are nested in the
        // response from the provider.
        return (new User)->setRaw($user)->map([
            'id'    => $user['id'],
            'name'  => $user['displayName'],
            'email' => $user['mail'] ?? $user['userPrincipalName'],

            // The following values are not always required by the provider. We
            // cannot guarantee they will exist in the $user array.
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
        ]);
    }

    public static function additionalConfigKeys(): array
    {
        return [
            'tenant_id',
            'user_endpoint_version',
        ];
    }
}
