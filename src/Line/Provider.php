<?php

namespace SocialiteProviders\Line;

use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'LINE';

    protected $scopeSeparator = ' ';

    protected $scopes = [
        'openid',
        'profile',
        'email',
    ];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://access.line.me/oauth2/v2.1/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.line.me/oauth2/v2.1/token';
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param  string  $token
     * @return array
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.line.me/v2/profile',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param  array  $user
     * @return \Laravel\Socialite\User
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['userId'] ?? $user['sub'] ?? null,
            'nickname' => null,
            'name'     => $user['displayName'] ?? $user['name'] ?? null,
            'avatar'   => $user['pictureUrl'] ?? $user['picture'] ?? null,
            'email'    => $user['email'] ?? null,
        ]);
    }

    /**
     * @return \SocialiteProviders\Manager\OAuth2\User
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException;
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        if ($jwt = $response['id_token'] ?? null) {
            $bodyb64 = explode('.', $jwt)[1];
            $user = $this->mapUserToObject(json_decode(base64_decode(strtr($bodyb64, '-_', '+/')), true));
        } else {
            $user = $this->mapUserToObject($this->getUserByToken(
                $token = $this->parseAccessToken($response)
            ));
        }

        $this->credentialsResponseBody = $response;

        if ($user instanceof User) {
            $user->setAccessTokenResponseBody($this->credentialsResponseBody);
        }

        return $user->setToken($this->parseAccessToken($response))
            ->setRefreshToken($this->parseRefreshToken($response))
            ->setExpiresIn($this->parseExpiresIn($response));
    }

    public static function additionalConfigKeys(): array
    {
        return [
            'bot_prompt',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);

        $botPrompt = $this->getConfig('bot_prompt');
        if (! empty($botPrompt)) {
            $fields['bot_prompt'] = $botPrompt;
        }

        return $fields;
    }
}
