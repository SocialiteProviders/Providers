<?php

namespace App\Providers\Socialite\TikTokShop;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://partner.tiktokshop.com/docv2/page/678e3a3292b0f40314a92d75
 */
class Provider extends AbstractProvider implements ProviderInterface
{
    public const IDENTIFIER = 'TIKTOKSHOP';
    protected $scopes = [];

    /**
     * Build the authorization URL.
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://auth.tiktok-shops.com/api/v2/oauth/authorize',
            $state
        );
    }

    /**
     * Endpoint to exchange code for tokens.
     */
    protected function getTokenUrl()
    {
        return 'https://auth.tiktok-shops.com/api/v2/token/get';
    }

    /**
     * Override user() so we can map the shop data directly from the token payload.
     */
    public function user()
    {
        // 1) Exchange auth code for token data
        $response = $this->getHttpClient()->get($this->getTokenUrl(), [
            'query' => $this->getTokenFields($this->request->get('code')),
        ]);

        $json      = json_decode((string) $response->getBody(), true);
        $tokenData = $json['data'] ?? $json;

        // 2) Map to Socialite User
        $user = (new User)->setRaw($tokenData)->map([
            'id'       => $tokenData['open_id']        ?? null,
            'nickname' => $tokenData['seller_name']    ?? null,
            'name'     => $tokenData['seller_name']    ?? null,
            'email'    => null,
            'avatar'   => null,
        ]);

        // 3) Attach tokens
        $user->token        = $tokenData['access_token']             ?? null;
        $user->refreshToken = $tokenData['refresh_token']            ?? null;
        $user->expiresIn    = $tokenData['access_token_expire_in']   ?? null;

        return $user;
    }

    /**
     * Build the query parameters for the token request.
     */
    protected function getTokenFields($code)
    {
        return [
            'app_key'     => $this->clientId,
            'app_secret'  => $this->clientSecret,
            'auth_code'   => $code,
            'grant_type'  => 'authorized_code',
        ];
    }
}
