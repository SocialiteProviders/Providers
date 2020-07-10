<?php

namespace SocialiteProviders\Flexkids;

use Illuminate\Auth\AuthenticationException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * Class Provider.
 */
class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'FLEXKIDS';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['basic'];

    /**
     * @var null
     */
    protected $idToken = null;

    protected $uniqueUserId = null;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $bashUrl = $this->buildAuthUrlFromBase($this->getConfig('authurl'), $state);
        $url = sprintf('%s&resource=%s', $bashUrl, urlencode($this->getConfig('resource')));

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getConfig('server').'/v2/oauth/connect/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getConfig('server').'/v2/application-user', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        if (array_key_exists('data', $data)) {
            return $data['data'];
        }

        throw new AuthenticationException();
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $this->getUniqueUserId(),
            'nickname' => null,
            'name'     => $user['name'],
            'email'    => $user['username'],
            'avatar'   => $user['person']['actions']['avatar']['href'],
        ]);
    }

    /**
     * @param string $code
     *
     * @throws AuthenticationException
     *
     * @return array
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => $this->getTokenFields($code),
        ]);

        $data = json_decode($response->getBody(), true);

        if (array_key_exists('data', $data)) {
            $this->setIdToken($data['data']['id_token']);
            $tokens = explode('.', $data['data']['id_token']);
            $payload = json_decode(base64_decode($tokens[1]), true);
            $this->setUniqueUserId($payload['sub']);

            return $data['data'];
        }

        throw new AuthenticationException();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type'     => 'authorization_code',
            'resource'       => $this->getConfig('resource'),
            'user_api_token' => $this->getConfig('apiuser'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['resource', 'apiuser', 'authurl', 'server'];
    }

    /**
     * @param $idToken
     *
     * @return Provider
     */
    private function setIdToken($idToken)
    {
        $this->idToken = $idToken;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIdToken()
    {
        return $this->idToken;
    }

    /**
     * @return string|null
     */
    public function getUniqueUserId()
    {
        return $this->uniqueUserId;
    }

    /**
     * @param string $uniqueUserId
     *
     * @return Provider
     */
    public function setUniqueUserId($uniqueUserId)
    {
        $this->uniqueUserId = $uniqueUserId;

        return $this;
    }
}
