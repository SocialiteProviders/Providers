<?php

declare(strict_types=1);

namespace SocialiteProviders\DonationAlerts;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\Token;
use SocialiteProviders\Manager\ConfigTrait;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    use ConfigTrait;

    public const IDENTIFIER = 'DONATIONALERTS';

    protected $scopes = ['oauth-user-show'];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://www.donationalerts.com/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://www.donationalerts.com/oauth/token';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://www.donationalerts.com/api/v1/user/oauth',
            [
                RequestOptions::HEADERS => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user): User
    {
        $rawUser = Arr::get($user, 'data');

        return (new User)->setRaw($rawUser)->map([
            'id'       => $rawUser['id'],
            'nickname' => $rawUser['code'],
            'name'     => $rawUser['name'],
            'email'    => Arr::get($rawUser, 'email'),
            'avatar'   => $rawUser['avatar'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshToken($refreshToken): Token
    {
        $response = $this->getRefreshTokenResponse($refreshToken);

        return new Token(
            Arr::get($response, 'access_token'),
            Arr::get($response, 'refresh_token'),
            Arr::get($response, 'expires_in'),
            Arr::get($response, 'scope', $this->scopes)
        );
    }
}
