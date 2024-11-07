<?php

namespace SocialiteProviders\Notion;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'NOTION';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getInstanceUri().'oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getInstanceUri().'oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        return $this->credentialsResponseBody;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['bot_id'],
            'nickname' => $user['workspace_name'],
            'name'     => $user['workspace_name'],
            'avatar'   => $user['workspace_icon'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
                'Content-Type'  => 'application/json',
            ],
            RequestOptions::JSON => [
                'grant_type'   => 'authorization_code',
                'code'         => $code,
                'redirect_uri' => $this->redirectUrl,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function getInstanceUri()
    {
        return $this->getConfig('instance_uri', 'https://api.notion.com/v1/');
    }

    public static function additionalConfigKeys(): array
    {
        return ['instance_uri'];
    }
}
