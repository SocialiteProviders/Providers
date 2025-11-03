<?php

namespace SocialiteProviders\TikTokShop;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://partner.tiktokshop.com/docv2/page/678e3a3292b0f40314a92d75
 */
class Provider extends AbstractProvider implements ProviderInterface
{
    public const IDENTIFIER = 'TIKTOKSHOP';
    protected $scopes = [];

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://auth.tiktok-shops.com/api/v2/oauth/authorize',
            $state
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getTokenUrl()
    {
        return 'https://auth.tiktok-shops.com/api/v2/token/get';
    }

    /**
     * {@inheritDoc}
     */
    public function user()
    {
        $response = $this->getHttpClient()->get($this->getTokenUrl(), [
            'query' => $this->getTokenFields($this->request->get('code')),
        ]);

        $json = json_decode((string)$response->getBody(), true);
        $tokenData = $json['data'] ?? $json;

        $user = $this->mapUserToObject($tokenData);

        $user->token = $tokenData['access_token'] ?? null;
        $user->refreshToken = $tokenData['refresh_token'] ?? null;
        $user->expiresIn = $tokenData['access_token_expire_in'] ?? null;

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    protected function getTokenFields($code)
    {
        return [
            'app_key' => $this->clientId,
            'app_secret' => $this->clientSecret,
            'auth_code' => $code,
            'grant_type' => 'authorized_code',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['open_id'] ?? null,
            'nickname' => $user['seller_name'] ?? null,
            'name' => $user['seller_name'] ?? null,
            'email' => null,
            'avatar' => null,
        ]);
    }
}
