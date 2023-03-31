<?php

namespace SocialiteProviders\Keycloak;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\Exception\InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'KEYCLOAK';

    protected $scopeSeparator = ' ';

    protected $scopes = ['openid'];

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['base_url', 'realms'];
    }

    protected function getBaseUrl()
    {
        return rtrim(rtrim($this->getConfig('base_url'), '/').'/realms/'.$this->getConfig('realms', 'master'), '/');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/protocol/openid-connect/auth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUrl().'/protocol/openid-connect/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl().'/protocol/openid-connect/userinfo', [
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
        return (new User())->setRaw($user)->map([
            'id'        => Arr::get($user, 'sub'),
            'nickname'  => Arr::get($user, 'preferred_username'),
            'name'      => Arr::get($user, 'name'),
            'email'     => Arr::get($user, 'email'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * Return logout endpoint with redirect_uri, clientId, idTokenHint
     * and optional parameters by a key value array.
     *
     * @param string|null $redirectUri
     * @param string|null $clientId
     * @param string|null $idTokenHint
     * @param array       $additionalParameters
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    public function getLogoutUrl(?string $redirectUri = null, ?string $clientId = null, ?string $idTokenHint = null, ...$additionalParameters): string
    {
        $logoutUrl = $this->getBaseUrl().'/protocol/openid-connect/logout';

        // Keycloak v18+ or before
        if ($redirectUri === null) {
            return $logoutUrl;
        }

        // Before Keycloak v18
        if ($clientId === null && $idTokenHint === null) {
            return $logoutUrl.'?redirect_uri='.urlencode($redirectUri);
        }

        // Keycloak v18+
        // https://www.keycloak.org/docs/18.0/securing_apps/index.html#logout
        // https://openid.net/specs/openid-connect-rpinitiated-1_0.html
        $logoutUrl .= '?post_logout_redirect_uri='.urlencode($redirectUri);

        // Either clientId or idTokenHint
        // is required for the post redirect to work.
        if ($clientId !== null) {
            $logoutUrl .= '&client_id='.urlencode($clientId);
        }

        if ($idTokenHint !== null) {
            $logoutUrl .= '&id_token_hint='.urlencode($idTokenHint);
        }

        foreach ($additionalParameters as $parameter) {
            if (!is_array($parameter) || sizeof($parameter) > 1) {
                throw new InvalidArgumentException('Invalid argument. Expected an array with a key and a value.');
            }

            $parameterKey = array_keys($parameter)[0];
            $parameterValue = array_values($parameter)[0];

            $logoutUrl .= "&{$parameterKey}=".urlencode($parameterValue);
        }

        return $logoutUrl;
    }
}
