<?php

namespace SocialiteProviders\InstagramBasic;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
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
        $queryParameters = [
            'access_token' => $token,
            'fields'       => implode(',', $this->fields),
        ];

        if (! empty($this->clientSecret)) {
            $queryParameters['appsecret_proof'] = hash_hmac('sha256', $token, $this->clientSecret);
        }

        $response = $this->getHttpClient()->get('https://graph.instagram.com/me', [
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
            ],
            RequestOptions::QUERY => $queryParameters,
        ]);

        return json_decode((string) $response->getBody(), true);
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
        $fields = parent::getCodeFields($state);
        $fields['app_id'] = $fields['client_id'];
        unset($fields['client_id']);
    
        return $fields;
    }

    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode((string) $response->getBody(), true);

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
