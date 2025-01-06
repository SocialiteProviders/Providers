<?php

namespace SocialiteProviders\VKontakte;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\InvalidStateException;
use RuntimeException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    protected $fields = ['id', 'email', 'first_name', 'last_name', 'screen_name', 'photo_200'];

    public const IDENTIFIER = 'VKONTAKTE';

    protected $scopes = ['email'];

    /**
     * Last API version.
     */
    public const VERSION = '5.131';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://oauth.vk.com/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://oauth.vk.com/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $formToken = [];

        if (is_array($token)) {
            $formToken['email'] = $token['email'] ?? null;

            $token = $token['access_token'];
        }

        $response = $this->getHttpClient()->get('https://api.vk.com/method/users.get', [
            RequestOptions::QUERY => [
                'access_token' => $token,
                'fields'       => implode(',', $this->fields),
                'lang'         => $this->getConfig('lang', 'en'),
                'v'            => self::VERSION,
            ],
        ]);

        $response = json_decode((string) $response->getBody(), true);

        if (! is_array($response) || ! isset($response['response'][0])) {
            throw new RuntimeException(sprintf(
                'Invalid JSON response from VK: %s', $response->getBody()
            ));
        }

        return array_merge($formToken, $response['response'][0]);
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException;
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        $user = $this->mapUserToObject($this->getUserByToken($response));

        $this->credentialsResponseBody = $response;

        if ($user instanceof User) {
            $user->setAccessTokenResponseBody($this->credentialsResponseBody);
        }

        return $user->setToken($this->parseAccessToken($response))
            ->setExpiresIn($this->parseExpiresIn($response));
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => Arr::get($user, 'id'),
            'nickname' => Arr::get($user, 'screen_name'),
            'name'     => trim(Arr::get($user, 'first_name').' '.Arr::get($user, 'last_name')),
            'email'    => Arr::get($user, 'email'),
            'avatar'   => Arr::get($user, 'photo_200'),
        ]);
    }

    /**
     * Set the user fields to request from Vkontakte.
     *
     * @param  array  $fields
     * @return $this
     */
    public function fields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    public static function additionalConfigKeys(): array
    {
        return ['lang'];
    }
}
