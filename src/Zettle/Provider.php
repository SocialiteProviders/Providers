<?php

namespace SocialiteProviders\Zettle;

use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use SocialiteProviders\Manager\OAuth2\User;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class ZettleProvider extends AbstractProvider
{
    protected $usesPKCE = true;

    /**
     * Zettle OAuth base url.
     */
    private const BASE_URL = 'https://oauth.zettle.com';

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            self::BASE_URL.'/authorize',
            $state
        );
    }

    protected function getTokenUrl()
    {
        return self::BASE_URL.'/token';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            self::BASE_URL.'/users/self',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['uuid'],
        ]);
    }

    /**
     * Remove a Zettle merchant from your app by disconnecting them from the app. This can be useful to clean up registered webhooks and remove access.
     *
     * @param string $token
     */
    public function disconnect($token): void
    {
        $this->getHttpClient()->delete(
            self::BASE_URL.'/application-connections/self',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );
    }
}
