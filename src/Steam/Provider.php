<?php

namespace SocialiteProviders\Steam;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use RuntimeException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * Steam socialite provider, based on `laravel-steam-auth` by @invisnik.
 *
 * @see https://github.com/invisnik/laravel-steam-auth
 */
class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'STEAM';

    /**
     * @var string
     */
    public $steamId;

    /**
     * @var array
     */
    protected $customRequestOptions = [];

    /**
     * @var string
     */
    public const OPENID_URL = 'https://steamcommunity.com/openid/login';

    /**
     * @var string
     */
    public const STEAM_INFO_URL = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=%s&steamids=%s';

    /**
     * @var string
     */
    public const OPENID_SIG = 'openid_sig';

    /**
     * @var string
     */
    public const OPENID_SIGNED = 'openid_signed';

    /**
     * @var string
     */
    public const OPENID_ASSOC_HANDLE = 'openid_assoc_handle';

    /**
     * @var string
     */
    public const OPENID_NS = 'http://specs.openid.net/auth/2.0';

    /**
     * @var string
     */
    public const OPENID_ERROR = 'openid_error';

    /**
     * {@inheritdoc}
     */
    protected $stateless = true;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if (!$this->validate()) {
            $error = $this->getParams()['openid.error'] ?? 'unknown error';

            throw new OpenIDValidationException('Failed to validate OpenID login: '.$error);
        }

        return $this->mapUserToObject($this->getUserByToken($this->steamId));
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessToken($body)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        if (is_null($token)) {
            return null;
        }

        if (empty($this->clientSecret)) {
            throw new RuntimeException('The Steam API key has not been specified.');
        }

        $response = $this->getHttpClient()->request(
            'GET',
            sprintf(self::STEAM_INFO_URL, $this->clientSecret, $token)
        );

        $contents = json_decode((string) $response->getBody(), true);

        return Arr::get($contents, 'response.players.0');
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['steamid'],
            'nickname' => Arr::get($user, 'personaname'),
            'name'     => Arr::get($user, 'realname'),
            'email'    => null,
            'avatar'   => Arr::get($user, 'avatarmedium'),
        ]);
    }

    /**
     * Build the Steam login URL.
     *
     * @return string
     */
    private function buildUrl()
    {
        $realm = $this->getConfig('realm', $this->request->server('HTTP_HOST'));

        $params = [
            'openid.ns'         => self::OPENID_NS,
            'openid.mode'       => 'checkid_setup',
            'openid.return_to'  => $this->redirectUrl,
            'openid.realm'      => sprintf('%s://%s', $this->request->getScheme(), $realm),
            'openid.identity'   => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ];

        return self::OPENID_URL.'?'.http_build_query($params, '', '&');
    }

    /**
     * Checks the steam login.
     *
     * @throws \SocialiteProviders\Steam\OpenIDValidationException
     *
     * @return bool
     */
    public function validate()
    {
        if (!$this->requestIsValid()) {
            return false;
        }

        if (!$this->validateHost($this->request->get('openid_return_to'))) {
            throw new OpenIDValidationException('Invalid return_to host');
        }

        $requestOptions = $this->getDefaultRequestOptions();
        $customOptions = $this->getCustomRequestOptions();

        if (!empty($customOptions) && is_array($customOptions)) {
            $requestOptions = array_merge($requestOptions, $customOptions);
        }

        $response = $this->getHttpClient()->request('POST', self::OPENID_URL, $requestOptions);

        $results = $this->parseResults((string) $response->getBody());

        $isValid = $results['is_valid'] === 'true';

        if ($isValid) {
            $this->parseSteamID();
        }

        return $isValid;
    }

    /**
     * Validates if the request object has required stream attributes.
     *
     * @return bool
     */
    private function requestIsValid()
    {
        return $this->request->has(self::OPENID_ASSOC_HANDLE)
            && $this->request->has(self::OPENID_SIGNED)
            && $this->request->has(self::OPENID_SIG);
    }

    /**
     * @return array
     */
    public function getDefaultRequestOptions()
    {
        return [
            RequestOptions::FORM_PARAMS => $this->getParams(),
            RequestOptions::PROXY       => $this->getConfig('proxy'),
        ];
    }

    /**
     * @return array
     */
    public function getCustomRequestOptions()
    {
        return $this->customRequestOptions;
    }

    /**
     * Get param list for openId validation.
     *
     * @return array
     */
    public function getParams()
    {
        $params = [
            'openid.assoc_handle' => $this->request->get(self::OPENID_ASSOC_HANDLE),
            'openid.signed'       => $this->request->get(self::OPENID_SIGNED),
            'openid.sig'          => $this->request->get(self::OPENID_SIG),
            'openid.ns'           => self::OPENID_NS,
            'openid.mode'         => 'check_authentication',
            'openid.error'        => $this->request->get(self::OPENID_ERROR),
        ];

        $signedParams = explode(',', $this->request->get(self::OPENID_SIGNED));

        foreach ($signedParams as $item) {
            $value = $this->request->get('openid_'.str_replace('.', '_', $item));
            $params['openid.'.$item] = $value;
        }

        return $params;
    }

    /**
     * Parse openID response to an array.
     *
     * @param string $results openid response body
     *
     * @return array
     */
    public function parseResults($results)
    {
        $parsed = [];
        $lines = explode("\n", $results);

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            $line = explode(':', $line, 2);
            $parsed[$line[0]] = $line[1];
        }

        return $parsed;
    }

    /**
     * Parse the steamID from the OpenID response.
     *
     * @return void
     */
    public function parseSteamID()
    {
        preg_match(
            '#^https?://steamcommunity.com/openid/id/([0-9]{17,25})#',
            $this->request->get('openid_claimed_id'),
            $matches
        );

        $this->steamId = isset($matches[1]) && is_numeric($matches[1]) ? $matches[1] : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['realm', 'proxy', 'allowed_hosts'];
    }

    /**
     * Validation of the domain available for authorization.
     *
     * @return bool
     */
    protected function validateHost(string $url): bool
    {
        $allowedHosts = $this->getConfig('allowed_hosts', []);

        return count($allowedHosts) === 0 || in_array(parse_url($url, PHP_URL_HOST), $allowedHosts, true);
    }
}
