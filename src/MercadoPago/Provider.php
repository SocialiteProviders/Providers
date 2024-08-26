<?php

namespace SocialiteProviders\MercadoPago;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://www.mercadopago.com.br/developers/pt/reference/oauth/_oauth_token/post
 */
class Provider extends AbstractProvider
{
    public const DOMAIN = [
        'AR' => 'https://auth.mercadopago.com.ar',
        'BR' => 'https://auth.mercadopago.com.br',
        'CL' => 'https://auth.mercadopago.cl',
        'CO' => 'https://auth.mercadopago.com.co',
        'MX' => 'https://auth.mercadopago.com.mx',
        'PE' => 'https://auth.mercadopago.com.pe',
        'UY' => 'https://auth.mercadopago.com.uy',
    ];

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
        return ['country'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null): array
    {
        $fields = [
            'client_id'     => $this->clientId,
            'response_type' => 'code',
            'platform_id'   => 'mp',
            'state'         => $state,
            'redirect_uri'  => $this->redirectUrl,
        ];

        return array_merge($fields, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $url = self::DOMAIN[config('services.mercadopago.country')] ?? 'https://auth.mercadopago.com';

        return $this->buildAuthUrlFromBase($url.'/authorization', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.mercadopago.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.mercadopago.com/users/me', [
            RequestOptions::HEADERS => [
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
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['nickname'],
            'name'     => $user['first_name'].' '.$user['last_name'],
            'email'    => $user['email'],
            'avatar'   => $user['thumbnail']['picture_url'] ?? null,
        ]);
    }
}
