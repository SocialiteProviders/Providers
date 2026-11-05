<?php

namespace SocialiteProviders\Tidal;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use RuntimeException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://developer.tidal.com/documentation/api-sdk/api-sdk-authorization
 * @see https://tidal-music.github.io/tidal-api-reference/
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'TIDAL';

    /**
     * Needed for GET /users/me, which carries the email, name and username.
     */
    protected $scopes = ['user.read'];

    protected $scopeSeparator = ' ';

    protected $usesPKCE = true;

    /**
     * Always include user.read, even when setScopes() replaces the defaults.
     */
    public function getScopes(): array
    {
        return array_values(array_unique(array_merge(['user.read'], parent::getScopes())));
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://login.tidal.com/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://auth.tidal.com/v1/oauth2/token';
    }

    /**
     * @throws RuntimeException
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://openapi.tidal.com/v2/users/me', [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/vnd.api+json',
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        $user = json_decode((string) $response->getBody(), true);

        if (! is_array($user) || Arr::get($user, 'data.id') === null) {
            $error = is_array($user) ? Arr::get($user, 'errors.0', []) : [];
            $detail = implode(' ', array_filter([
                Arr::get($error, 'status'),
                Arr::get($error, 'code'),
                Arr::get($error, 'detail'),
            ]));

            throw new RuntimeException('TIDAL user response is missing data'.($detail !== '' ? ': '.$detail : '.'));
        }

        return $user;
    }

    /**
     * The response is a JSON:API document, so the profile sits under data.attributes.
     */
    protected function mapUserToObject(array $user)
    {
        $attributes = Arr::get($user, 'data.attributes', []);

        $name = trim(Arr::get($attributes, 'firstName', '').' '.Arr::get($attributes, 'lastName', ''));

        return (new User)->setRaw($user)->map([
            'id'       => Arr::get($user, 'data.id'),
            'nickname' => Arr::get($attributes, 'username'),
            'name'     => $name !== '' ? $name : null,
            'email'    => Arr::get($attributes, 'email'),
        ]);
    }
}
