<?php
namespace SocialiteProviders\Mattermost;

use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'MATTERMOST';
    /**
     * {@inheritdoc}
     */
    protected $scopes = [];
    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';
    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getInstanceUri().'oauth/authorize', $state);
    }
    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getInstanceUri().'oauth/access_token';
    }
    /**
     * mattermost api version
     * This varibale used for user avatar url and more
     */
    protected $api_version = 'v4';
    /**
     * Mattermost
     * @return String
     */
    protected function getAPIVersion()
    {
        return $this->getConfig('api_version', $this->api_version);
    }
    protected function getAPIBase()
    {
        return $this->getInstanceUri()."api/{$this->getAPIVersion()}";
    }
    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
      "{$this->getInstanceUri()}api/{$this->getAPIVersion()}/users/me",
      [
        'headers' => [
          'Authorization' => 'BEARER '.$token,
        ],
      ]
    );
        return json_decode($response->getBody(), true);
    }
    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $user = ( new User() )->setRaw($user)->map(
      [
        'id'       => $user['id'],
        'nickname' => $user['nickname'],
        'name'     => $user['username'],
        'email'    => $user['email'],
        'avatar'   => "{$this->getAPIBase()}/users/{$user['id']}/image?time={$user['last_picture_update']}",
      ]
    );
        return $user;
    }
    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        $a = parent::getTokenFields($code)+['grant_type' => 'authorization_code'];
        return $a;
    }
    /**
     * {@inheritdoc}
     */
    protected function getInstanceUri()
    {
        $uri = $this->getConfig('instance_uri', null);
        if (! $uri) {
            throw new \InvalidArgumentException(
        'No instance_uri. ENV['.self::IDENTIFIER.'_INSTANCE_URI]=https://mm.example.com/ must be provided.'
      );
        }
        return $uri;
    }
    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['instance_uri'];
    }
}
