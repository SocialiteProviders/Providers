<?php

namespace SocialiteProviders\Nuvemshop;

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
    protected $scopes = [
        'read_content',
        'write_content',
        'read_products',
        'write_products',
        'read_customers',
        'write_customers',
        'read_orders',
        'write_orders',
        'read_coupons',
        'write_coupons',
        'write_scripts',
        'write_shipping',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase("https://www.tiendanube.com/apps/{$this->clientId}/authorize", $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://www.tiendanube.com/apps/authorize/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token): array
    {
        [$bearerToken, $userId] = explode('.', $token, 2);

        $response = $this->getHttpClient()->get(
            "https://api.nuvemshop.com.br/v1/$userId/store",
            [
                RequestOptions::HEADERS => [
                    'Authentication' => 'bearer '.$bearerToken,
                    'User-Agent'     => $this->clientId,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'name'     => $user['name'],
            'nickname' => null,
            'email'    => $user['email'],
            'avatar'   => null,
        ]);
    }
}
