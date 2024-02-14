<?php

namespace SocialiteProviders\MercadoLibre;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://developers.mercadolibre.com.ar/es_ar/autenticacion-y-autorizacion
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'MERCADOLIBRE';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['offline_access', 'read'];

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
    protected function getAuthUrl($state)
    {
        $domains = [
            'AR' => 'https://auth.mercadolibre.com.ar',
            'BO' => 'https://auth.mercadolibre.com.bo',
            'BR' => 'https://auth.mercadolivre.com.br',
            'CL' => 'https://auth.mercadolibre.cl',
            'CO' => 'https://auth.mercadolibre.com.co',
            'CR' => 'https://auth.mercadolibre.co.cr',
            'DO' => 'https://auth.mercadolibre.com.do',
            'EC' => 'https://auth.mercadolibre.com.ec',
            'GT' => 'https://auth.mercadolibre.com.gt',
            'HN' => 'https://auth.mercadolibre.com.hn',
            'MX' => 'https://auth.mercadolibre.com.mx',
            'NI' => 'https://auth.mercadolibre.com.ni',
            'PA' => 'https://auth.mercadolibre.com.pa',
            'PE' => 'https://auth.mercadolibre.com.pe',
            'PY' => 'https://auth.mercadolibre.com.py',
            'SV' => 'https://auth.mercadolibre.com.sv',
            'UY' => 'https://auth.mercadolibre.com.uy',
            'VE' => 'https://auth.mercadolibre.com.ve',
        ];

        $countryCode = $this->getConfig('country', 'AR');

        return $this->buildAuthUrlFromBase($domains[$countryCode].'/authorization', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.mercadolibre.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.mercadolibre.com/users/me', [
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
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['nickname'],
            'name'     => $user['first_name'].' '.$user['last_name'],
            'email'    => $user['email'],
            'avatar'   => $user['thumbnail']['picture_url'] ?? null,
        ]);
    }
}
