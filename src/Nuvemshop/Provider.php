<?php

namespace SocialiteProviders\Nuvemshop;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://tiendanube.github.io/api-documentation/authentication
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'NUVEMSHOP';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['read'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * Returns the configured user id that we're authenticating with
     *
     * @return string
     */
    private function getClientId()
    {
        return $this->getConfig('client_id');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            sprintf(
                'https://www.tiendanube.com/apps/%s/authorize',
                $this->getClientId()
            ),
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://www.tiendanube.com/apps/authorize/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token, $userId, $userAgent)
    {
        $response = $this->getHttpClient()->get(
            "https://api.nuvemshop.com.br/v1/$userId/store",
            [
                RequestOptions::HEADERS => [
                    'Authentication' => 'bearer ' . $token,
                    'User-Agent' => $userAgent
                ]
            ]
        );

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['user_id'],
            'name' => $user['name'],
            'email' => $user['email']
        ]);
    }
}
