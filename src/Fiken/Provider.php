<?php

namespace SocialiteProviders\Fiken;

use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Fiken Oauth base url.
     */
    private const URL = 'https://fiken.no';

    /**
     * Fiken api base url.
     */
    private const BASE_URL = 'https://api.fiken.no/api/v2';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(self::URL.'/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return self::URL.'/oauth/token';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            self::BASE_URL.'/user',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'name'  => $user['name'],
            'email' => $user['email'],
        ]);
    }

    protected function getTokenHeaders($code)
    {
        return $this->getAuthenticationHeader();
    }

    protected function getTokenFields($code)
    {
        return [
            'code'         => $code,
            'grant_type'   => 'authorization_code',
            'redirect_uri' => $this->redirectUrl,
        ];
    }

    protected function getRefreshTokenResponse($refreshToken)
    {
        $response = $this->getHttpClient()->post(
            $this->getTokenUrl(),
            [
                RequestOptions::HEADERS     => $this->getAuthenticationHeader(),
                RequestOptions::FORM_PARAMS => [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Revoke a token
     *
     * @param  string  $token  Access token
     */
    public function revokeToken(string $token): ResponseInterface
    {
        return $this->getHttpClient()->post(
            self::URL.'/oauth/revoke',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );
    }

    /**
     * Fiken requires the Client ID and Client Secret to be sent using basic authentication
     * for some endpoints during the Oauth flow.
     */
    private function getAuthenticationHeader(): array
    {
        $auth = base64_encode($this->clientId.':'.$this->clientSecret);

        return ['Authorization' => 'Basic '.$auth];
    }
}
