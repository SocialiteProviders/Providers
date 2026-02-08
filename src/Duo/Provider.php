<?php

namespace SocialiteProviders\Duo;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'DUO';

    protected $scopes = ['openid', 'profile', 'email'];

    protected $scopeSeparator = ' ';

    public static function additionalConfigKeys(): array
    {
        return ['domain'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getDuoUrl('/oauth2/v1/authorize'), $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return $this->getDuoUrl('/oauth2/v1/token');
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get($this->getDuoUrl('/oauth2/v1/userinfo'), [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id'       => Arr::get($user, 'sub'),
            'nickname' => Arr::get($user, 'preferred_username') ?? Arr::get($user, 'email'),
            'name'     => Arr::get($user, 'name'),
            'email'    => Arr::get($user, 'email'),
            'avatar'   => Arr::get($user, 'picture'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code): array
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * Get the base Duo SSO URL with an optional appended path.
     *
     * @param  string  $path
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function getDuoUrl(string $path = ''): string
    {
        $domain = $this->getConfig('domain');

        if (empty($domain)) {
            throw new InvalidArgumentException('Duo SSO domain is required. Set DUO_DOMAIN in your .env file.');
        }

        // Supporting both full URL and subdomain
        $baseUrl = (string) Str::startsWith($domain, ['http://', 'https://'])
            ? Str::of($domain)->rtrim('/')->value()
            : Str::of($domain)
                ->rtrim('.')
                ->prepend('https://')
                ->append('.sso.duosecurity.com')
                ->value();

        return $baseUrl.$path;
    }
}
