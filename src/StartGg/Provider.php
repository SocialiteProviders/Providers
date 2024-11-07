<?php

namespace SocialiteProviders\StartGg;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://dev.start.gg/docs/oauth/oauth-overview
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'STARTGG';

    protected $scopes = [
        'user.identity',
        'user.email',
    ];

    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(
            'https://start.gg/oauth/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://api.start.gg/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->post(
            'https://api.start.gg/gql/alpha',
            [
                RequestOptions::HEADERS => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
                RequestOptions::FORM_PARAMS => [
                    'query' => 'query {
                        currentUser {
                            discriminator
                            email
                            id
                            images (type: "profile") {
                                url
                            }
                            name
                            player {
                                gamerTag
                            }
                        }
                    }',
                ],
            ]
        );

        $response = json_decode((string) $response->getBody(), true);

        return $response['data']['currentUser'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id'            => $user['id'],
            'nickname'      => Arr::get($user, 'player.gamerTag'),
            'name'          => Arr::get($user, 'name'),
            'email'         => Arr::get($user, 'email'),
            'avatar'        => Arr::get($user, 'images.0.url'),
            'discriminator' => $user['discriminator'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getRefreshTokenResponse($refreshToken)
    {
        $response = $this->getHttpClient()->post('https://api.start.gg/oauth/refresh', [
            RequestOptions::HEADERS     => ['Content-Type' => 'application/json'],
            RequestOptions::FORM_PARAMS => [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
                'scope'         => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri'  => $this->redirectUrl,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }
}
