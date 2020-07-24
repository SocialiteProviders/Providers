<?php

namespace SocialiteProviders\InstagramBasic;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'INSTAGRAMBASIC';

    /**
     * The user fields being requested.
     *
     * @var array
     */
    protected $fields = ['account_type', 'id', 'username'];

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['user_profile'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://api.instagram.com/oauth/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.instagram.com/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $meUrl = 'https://graph.instagram.com/me?access_token='.$token.'&fields='.implode(',', $this->fields);

        if (!empty($this->clientSecret)) {
            $appSecretProof = hash_hmac('sha256', $token, $this->clientSecret);
            $meUrl .= '&appsecret_proof='.$appSecretProof;
        }
        $response = $this->getHttpClient()->get($meUrl, [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'           => $user['id'],
            'nickname'     => $user['username'],
            'name'         => null,
            'email'        => null,
            'avatar'       => null,
            'account_type' => $user['account_type'],
            'media_count'  => $user['media_count'] ?? null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        return [
            'state'         => $state,
            'response_type' => 'code',
            'app_id'        => $this->clientId,
            'redirect_uri'  => $this->redirectUrl,
            'scope'         => $this->formatScopes($this->scopes, $this->scopeSeparator),
        ];
    }

    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'form_params' => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode($response->getBody(), true);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return [
            'code'         => $code,
            'app_id'       => $this->clientId,
            'app_secret'   => $this->clientSecret,
            'grant_type'   => 'authorization_code',
            'redirect_uri' => $this->redirectUrl,
        ];
    }
}
