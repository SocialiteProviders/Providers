<?php

namespace SocialiteProviders\SciStarter;

use Illuminate\Support\Arr;
use GuzzleHttp\ClientInterface;
use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'SCISTARTER';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['login extensive'];

    /**
     * {@inheritdoc}
     */
    protected $encodingType = PHP_QUERY_RFC3986;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://scistarter.com/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://scistarter.com/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://scistarter.com/api/user_info', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
            'query' => [
              'client_id' => $this->clientId,
              'key' => $this->clientSecret,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
          'id' => Arr::get($user, 'user_id'),
          'nickname' => Arr::get($user, 'handle'),
          'email' => Arr::get($user, 'email'),
          'first_name' => Arr::get($user, 'first_name'),
          'last_name' => Arr::get($user, 'last_name'),
          'profile_id' => Arr::get($user, 'profile_id'),
          'profile_url' => Arr::get($user, 'url'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return [
          'client_id' => $this->clientId,
          'code' => $code,
          'grant_type' => 'authorization_code',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            $postKey => $this->getTokenFields($code),
            'query' => [
              'key' => $this->clientSecret, // key instead of client_secret
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
}
