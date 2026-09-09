<?php

namespace SocialiteProviders\Vipps;

use Composer\InstalledVersions;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;
use UnexpectedValueException;

/**
 * @see https://developer.vippsmobilepay.com/docs/APIs/login-api/api-guide/browser-flow-integration/
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'VIPPS';

    protected $scopes = ['openid', 'name', 'email'];

    protected $scopeSeparator = ' ';

    protected $usesPKCE = true;

    public static function additionalConfigKeys(): array
    {
        return ['test_mode', 'token_auth_method', 'merchant_serial_number'];
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getOpenIdConfiguration()['authorization_endpoint'], $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getOpenIdConfiguration()['token_endpoint'];
    }

    public function getScopes(): array
    {
        return array_values(array_unique(array_merge(['openid'], parent::getScopes())));
    }

    protected function getOpenIdConfiguration(): array
    {
        $baseUrl = filter_var($this->getConfig('test_mode', false), FILTER_VALIDATE_BOOLEAN)
            ? 'https://apitest.vipps.no'
            : 'https://api.vipps.no';

        return Cache::remember('socialiteproviders.vipps.openid.'.md5($baseUrl), 3600, function () use ($baseUrl) {
            $response = $this->getHttpClient()->get($baseUrl.'/access-management-1.0/access/.well-known/openid-configuration', [
                RequestOptions::HEADERS => $this->getHeaders(),
            ]);

            $configuration = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            foreach (['authorization_endpoint', 'token_endpoint', 'userinfo_endpoint'] as $endpoint) {
                $url = $configuration[$endpoint] ?? null;

                if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
                    throw new UnexpectedValueException('Invalid Vipps discovery endpoint: '.$endpoint);
                }
            }

            return $configuration;
        });
    }

    protected function getHeaders(): array
    {
        $headers = [
            'Accept'                       => 'application/json',
            'Vipps-System-Name'            => 'laravel-socialite',
            'Vipps-System-Version'         => InstalledVersions::isInstalled('laravel/socialite')
                ? (InstalledVersions::getPrettyVersion('laravel/socialite') ?? 'dev')
                : 'dev',
            'Vipps-System-Plugin-Name'     => 'socialiteproviders-vipps',
            'Vipps-System-Plugin-Version'  => InstalledVersions::isInstalled('socialiteproviders/vipps')
                ? (InstalledVersions::getPrettyVersion('socialiteproviders/vipps') ?? 'dev')
                : 'dev',
        ];

        if ($msn = $this->getConfig('merchant_serial_number')) {
            $headers['Merchant-Serial-Number'] = $msn;
        }

        return $headers;
    }

    protected function usesBasicAuthentication(): bool
    {
        $method = $this->getConfig('token_auth_method', 'client_secret_basic');

        if (! in_array($method, ['client_secret_basic', 'client_secret_post'], true)) {
            throw new InvalidArgumentException('Vipps token_auth_method must be client_secret_basic or client_secret_post.');
        }

        return $method === 'client_secret_basic';
    }

    protected function getTokenHeaders($code): array
    {
        $headers = $this->getHeaders();

        if ($this->usesBasicAuthentication()) {
            $headers['Authorization'] = 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret);
        }

        return $headers;
    }

    protected function getTokenFields($code): array
    {
        $fields = parent::getTokenFields($code);

        if ($this->usesBasicAuthentication()) {
            unset($fields['client_id'], $fields['client_secret']);
        }

        return $fields;
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getOpenIdConfiguration()['userinfo_endpoint'], [
            RequestOptions::HEADERS => array_merge($this->getHeaders(), [
                'Authorization' => 'Bearer '.$token,
            ]),
        ]);

        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function mapUserToObject(array $user)
    {
        if (! is_string($user['sub'] ?? null) || $user['sub'] === '') {
            throw new UnexpectedValueException('Vipps Userinfo response is missing sub.');
        }

        return (new User)->setRaw($user)->map([
            'id'    => $user['sub'],
            'name'  => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
        ]);
    }
}
