<?php

namespace SocialiteProviders\Blackboard;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'BLACKBOARD';

    protected $scopes = ['read'];

    protected $scopeSeparator = ' ';

    public static function additionalConfigKeys(): array
    {
        return ['subdomain'];
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::AUTH => [
                $this->clientId,
                $this->clientSecret,
            ],
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Get the base URL.
     *
     * @return string
     */
    protected function getBaseUrl(): string
    {
        return sprintf('https://%s.blackboard.com', $this->getConfig('subdomain'));
    }

    protected function getTokenUrl(): string
    {
        return $this->getBaseUrl().'/learn/api/public/v1/oauth2/token';
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/learn/api/public/v1/oauth2/authorizationcode', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $uuid = $this->credentialsResponseBody['user_id'];
        $url = sprintf(
            $this->getBaseUrl().'/learn/api/public/v1/users/uuid:%s',
            $uuid
        );

        $response = $this->getHttpClient()->get($url, [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
                'Accept'        => 'application/json',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'     => $user['id'],
            'email'  => Arr::get($user, 'contact.email'),
            'avatar' => Arr::get($user, 'avatar.viewUrl'),
        ]);
    }
}
