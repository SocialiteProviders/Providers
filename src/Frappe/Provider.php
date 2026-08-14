<?php

namespace SocialiteProviders\Frappe;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const string IDENTIFIER = 'FRAPPE';

    protected $scopeSeparator = ' ';

    protected $scopes = ['openid'];

    public static function additionalConfigKeys(): array
    {
        return ['base_url', 'fields'];
    }

    /** {@inheritDoc} */
    public function getScopes()
    {
        return array_values(array_unique(array_merge(
            $this->scopes,
            $this->getConfig('fields', []) ? ['all'] : []
        )));
    }

    public function getLogoutUrl(): string
    {
        return $this->getBaseUrl().'/api/method/logout';
    }

    public function revokeToken(string $token): ResponseInterface
    {
        return $this->getHttpClient()->post(
            $this->getBaseUrl().'/api/method/frappe.integrations.oauth2.revoke_token',
            [RequestOptions::FORM_PARAMS => ['token' => $token]]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function refreshToken($refreshToken)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function getTokenUrl(): string
    {
        return $this->getBaseUrl().'/api/method/frappe.integrations.oauth2.get_token';
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(
            $this->getBaseUrl().'/api/method/frappe.integrations.oauth2.authorize',
            $state
        );
    }

    /**
     * The root URL of the Frappe / ERPNext site, e.g. https://erp.example.com.
     */
    protected function getBaseUrl(): string
    {
        $baseUrl = $this->getConfig('base_url');

        if ($baseUrl === null) {
            throw new InvalidArgumentException('Missing Frappe "base_url" configuration.');
        }

        return rtrim($baseUrl, '/');
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            $this->getBaseUrl().'/api/method/frappe.integrations.oauth2.openid_profile',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        $user = json_decode((string) $response->getBody(), true);
        $fields = $this->getConfig('fields', []);

        if ($fields === []) {
            return $user;
        }

        $response = $this->getHttpClient()->get($this->getBaseUrl().'/api/resource/User', [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            RequestOptions::QUERY => [
                'fields'            => json_encode($fields),
                'filters'           => json_encode([['name', '=', Arr::get($user, 'email')]]),
                'limit_page_length' => 1,
            ],
        ]);

        $fields = json_decode((string) $response->getBody(), true)['data'][0] ?? [];

        return array_merge($fields, $user);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'          => Arr::get($user, 'sub') ?? Arr::get($user, 'email'),
            'nickname'    => null,
            'name'        => Arr::get($user, 'name'),
            'email'       => Arr::get($user, 'email'),
            'avatar'      => Arr::get($user, 'picture'),
            'given_name'  => Arr::get($user, 'given_name'),
            'family_name' => Arr::get($user, 'family_name'),
            'roles'       => Arr::get($user, 'roles'),
        ]);
    }
}
