<?php

namespace SocialiteProviders\Trovo;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'TROVO';

    protected $scopes = ['user_details_self'];

    protected $scopeSeparator = '+';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(
            'https://open.trovo.live/page/login.html',
            $state
        );
    }

    protected function getTokenUrl(): string
    {
        return 'https://open-api.trovo.live/openplatform/exchangetoken';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $fields = $this->getTokenFields($code);
        unset($fields['client_id']);

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => [
                'Accept'    => 'application/json',
                'Client-ID' => $this->clientId,
            ],
            RequestOptions::JSON => $fields,
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://open-api.trovo.live/openplatform/getuserinfo',
            [
                RequestOptions::HEADERS => [
                    'Accept'        => 'application/json',
                    'Client-ID'     => $this->clientId,
                    'Authorization' => 'OAuth '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['userId'],
            'nickname' => $user['nickName'] ?? null,
            'name'     => $user['userName'] ?? null,
            'email'    => $user['email'],
            'avatar'   => $user['profilePic'] ?? null,
        ]);
    }
}
