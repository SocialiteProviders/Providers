<?php

namespace SocialiteProviders\Blackboard;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'BLACKBOARD';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['read'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['subdomain'];
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $basic_auth = base64_encode($this->clientId.':'.$this->clientSecret);
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => ['Authorization' => 'Basic ' . $basic_auth],
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return "https://{$this->getConfig('subdomain')}.blackboard.com/learn/api/public/v1/oauth2/token";
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase("https://{$this->getConfig('subdomain')}.blackboard.com".
            '/learn/api/public/v1/oauth2/authorizationcode', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $uuid = $this->credentialsResponseBody['user_id'];
        $url = "https://{$this->getConfig('subdomain')}.blackboard.com/learn/api/public/v1/users/uuid:$uuid";

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
        return (new User())->setRaw($user)->map([
            'id'     => $user['id'],
            'email'  => Arr::get($user, 'contact.email'),
            'avatar' => Arr::get($user, 'avatar.viewUrl'),
        ]);
    }
}
