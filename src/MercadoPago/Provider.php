<?php

namespace App\Providers;

use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://www.mercadopago.com.br/developers/pt/reference/oauth/_oauth_token/post
 */
class MercadoPagoProvider extends AbstractProvider implements ProviderInterface
{
    public const IDENTIFIER = 'MERCADOPAGO';

    public const DOMAIN = [
        'AR' => 'https://auth.mercadopago.com.ar',
        'BO' => 'https://auth.mercadopago.com.bo',
        'BR' => 'https://auth.mercadopago.com.br',
        'CL' => 'https://auth.mercadopago.cl',
        'CO' => 'https://auth.mercadopago.com.co',
        'CR' => 'https://auth.mercadopago.co.cr',
        'DO' => 'https://auth.mercadopago.com.do',
        'EC' => 'https://auth.mercadopago.com.ec',
        'GT' => 'https://auth.mercadopago.com.gt',
        'HN' => 'https://auth.mercadopago.com.hn',
        'MX' => 'https://auth.mercadopago.com.mx',
        'NI' => 'https://auth.mercadopago.com.ni',
        'PA' => 'https://auth.mercadopago.com.pa',
        'PY' => 'https://auth.mercadopago.com.py',
        'PE' => 'https://auth.mercadopago.com.pe',
        'SV' => 'https://auth.mercadopago.com.sv',
        'UY' => 'https://auth.mercadopago.com.uy',
        'VE' => 'https://auth.mercadopago.com.ve',
    ];

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
     * {@inheritdoc }
     */
    protected function getCodeFields($state = null): array
    {
        $fields = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'platform_id' => 'mp',
            'state' => $state,
            'redirect_uri' => $this->redirectUrl,
        ];

        return array_merge($fields, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $url = "https://auth.mercadopago.com.br";
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
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['nickname'],
            'name'     => $user['first_name'].' '.$user['last_name'],
            'email'    => $user['email'],
            'avatar'   => $user['thumbnail']['picture_url'] ?? null,
        ]);
    }
}
