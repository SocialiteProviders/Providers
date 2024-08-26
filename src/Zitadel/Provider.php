<?php

namespace SocialiteProviders\Zitadel;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;
use InvalidArgumentException;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'ZITADEL';

    /** @inheritDoc */
    protected $scopeSeparator = ' ';

    /** @inheritDoc */
    protected $scopes = ['openid', 'profile', 'email'];

    /** @inheritDoc */
    public function getScopes()
    {
        $additionalScopes = [];
        if ($this->getConfig('organization_id')) {
            array_push($additionalScopes, 'urn:zitadel:iam:org:id:' . $this->getConfig('organization_id'));
        }
        if ($this->getConfig('project_id')) {
            array_push($additionalScopes, 'urn:zitadel:iam:org:project:id:' . $this->getConfig('project_id') . ':aud');
        }
        return  array_merge($this->scopes, $additionalScopes);
    }

    /** @inheritDoc */
    public static function additionalConfigKeys()
    {
        return [
            'base_url',
            'organization_id',
            'project_id',
            'post_logout_redirect_uri'
        ];
    }

    /** @inheritDoc */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getConfig('base_url') . '/oauth/v2/authorize', $state);
    }

    /** @inheritDoc */
    protected function getTokenUrl()
    {
        return $this->getConfig('base_url') . '/oauth/v2/token';
    }

    /** @inheritDoc */
    protected function getUserByToken($token)
    {
        return json_decode($this->getHttpClient()->get($this->getConfig('base_url') . '/oidc/v1/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $token
            ]
        ])->getBody(), true);
    }

    /** @inheritDoc */
    protected function parseApprovedScopes($body)
    {
        $scopes = parent::parseApprovedScopes($body);
        return array_unique(array_merge($scopes, $this->getScopes()));
    }

    /** @inheritDoc */
    public function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id' => Arr::get($user, 'sub'),
            'email' => Arr::get($user, 'email'),
            'name' => Arr::get($user, 'name'),
            'nickname' => Arr::get($user, 'preferred_username'),
            'avatar' => Arr::get($user, 'picture')
        ]);
    }

    /**
     * Return logout endpoint.
     *
     * @link https://zitadel.com/docs/apis/openidoauth/endpoints#end_session_endpoint
     *
     * @param string|null $idToken ID token from the access token response
     * @return string
     *
     * @throws Invalid
     */
    public function getLogoutUrl($idToken)
    {
        if (is_null($redirect = $this->getConfig('post_logout_redirect_uri'))) {
            throw new InvalidArgumentException('services.zitadel.post_logout_redirect_uri configuration is missing');
        }
        $query = [
            'id_token_hint' => $idToken,
            'client_id' => $this->clientId,
            'post_logout_redirect_uri' => $redirect,
            'state' => $this->getState()
        ];

        return $this->getConfig('base_url') . '/oidc/v1/end_session?' . http_build_query($query);
    }
}
