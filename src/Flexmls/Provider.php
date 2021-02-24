<?php

namespace SocialiteProviders\Flexmls;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'FLEXMLS';

    /**
     * {@inheritdoc}
     */
    protected $scopes = null;

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = [
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUrl,
            'response_type' => 'code',
        ];

        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        return array_merge($fields, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => [
                'Accept'                => 'application/json',
                'User-Agent'            => env('APP_NAME'),
                'X-SparkApi-User-Agent' => 'ThinkerySocialite',
            ],
            'form_params' => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://sparkplatform.com/oauth2', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://sparkapi.com/v1/oauth2/grant';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://sparkapi.com/v1/my/account', [
            'headers' => [
                'Authorization'         => 'Bearer '.$token,
                'User-Agent'            => env('APP_NAME'),
                'X-SparkApi-User-Agent' => 'ThinkerySocialite',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $profile = $user['D']['Results'][0];

        return (new User())->setRaw($profile)->map([
            'id'       => $profile['Id'],
            'name'     => $profile['Name'],
            'email'    => $profile['Emails'][0]['Address'],
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
}
