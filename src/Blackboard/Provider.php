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
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::AUTH        => [$this->clientId, $this->clientSecret],
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        $tokenUrlFormat = 'https://%s.blackboard.com/learn/api/public/v1/oauth2/token';

        return sprintf($tokenUrlFormat, $this->getConfig('subdomain'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $authUrlFormat = 'https://%s.blackboard.com/learn/api/public/v1/oauth2/authorizationcode';

        return $this->buildAuthUrlFromBase(sprintf($authUrlFormat, $this->getConfig('subdomain')), $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $uuid = $this->credentialsResponseBody['user_id'];
        $url = sprintf(
            'https://%s.blackboard.com/learn/api/public/v1/users/uuid:%s',
            $this->getConfig('subdomain'),
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
        return (new User())->setRaw($user)->map([
            'id'     => $user['id'],
            'email'  => Arr::get($user, 'contact.email'),
            'avatar' => Arr::get($user, 'avatar.viewUrl'),
        ]);
    }
}
