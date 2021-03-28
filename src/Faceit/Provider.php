<?php

namespace SocialiteProviders\Faceit;

use GuzzleHttp\ClientInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'FACEIT';

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://cdn.faceit.com/widgets/sso/index.html', $state);
    }

    protected function buildAuthUrlFromBase($url, $state)
    {
        return $url.'?'.http_build_query($this->getCodeFields($state), '', '&', $this->encodingType).'&redirect_popup=true';
    }

    protected function getTokenUrl()
    {
        return 'https://api.faceit.com/auth/v1/oauth/token';
    }

    protected function getUserByToken($token)
    {
        $meResponse = $this->getHttpClient()->get(
            'https://api.faceit.com/auth/v1/resources/userinfo',
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode($meResponse->getBody()->getContents(), true);
    }

    public function getAccessTokenResponse($code)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            $postKey  => $this->getTokenFields($code),
            'headers' => [
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
            ],
        ]);

        $this->credentialsResponseBody = json_decode($response->getBody(), true);

        return json_decode($response->getBody(), true);
    }

    protected function getTokenFields($code)
    {
        return [
            'code'       => $code,
            'grant_type' => 'authorization_code',
        ];
    }

    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['guid'],
            'nickname' => $user['nickname'],
            'avatar'   => $user['picture'] ?? null,
            'name'     => isset($user['given_name']) ? ($user['given_name'].' '.$user['family_name']) : null,
            'email'    => $user['email'] ?? null,
        ]);
    }
}
