<?php

namespace SocialiteProviders\Azure;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'AZURE';

    /**
     * The base Azure Graph URL.
     *
     * @var string
     */
    protected $graphUrl = 'https://graph.windows.net/myorganization/me';

    /**
     * The Graph API version for the request.
     *
     * @var string
     */
    protected $version = '1.5';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://login.microsoftonline.com/'.($this->config['tenant'] ?? 'common').'/oauth2/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://login.microsoftonline.com/common/oauth2/token';
    }

    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS =>  $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode((string) $response->getBody(), true);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->graphUrl, [
            RequestOptions::QUERY =>  [
                'api-version' => $this->version,
            ],
            RequestOptions::HEADERS =>  [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
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
            'id'    => $user['objectId'], 'nickname' => null, 'name' => $user['displayName'],
            'email' => $user['userPrincipalName'], 'avatar' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
            'resource'   => 'https://graph.windows.net',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['tenant'];
    }
}
