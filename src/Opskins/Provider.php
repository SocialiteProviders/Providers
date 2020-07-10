<?php

namespace SocialiteProviders\Opskins;

use GuzzleHttp\ClientInterface;
use RuntimeException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'OPSKINS';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['identity'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://oauth.opskins.com/v1/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://oauth.opskins.com/v1/access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'auth'   => [$this->clientId, $this->clientSecret],
            $postKey => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('http://api.opskins.com/IUser/GetProfile/v1/', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        $contents = $response->getBody()->getContents();

        $response = json_decode($contents, true);

        if (!is_array($response) || !isset($response['response'])) {
            throw new RuntimeException(sprintf(
                'Invalid JSON response from OPSKINS: %s',
                $contents
            ));
        }

        return $response['response'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['username'],
            'name'     => isset($user['name']['first']) ? ($user['name']['first'].' '.$user['name']['last']) : null,
            'email'    => isset($user['email']['contact_email']) ? $user['email']['contact_email'] : null,
            'avatar'   => $user['avatar'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['duration', 'mobile'];
    }
}
