<?php

namespace SocialiteProviders\Steam;

use GuzzleHttp\RequestOptions;
use Illuminate\Cache\NullStore;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
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
    public const OPENID_SIG = 'openid.sig';

    /**
     * @var string
     */
    public const OPENID_SIGNED = 'openid.signed';

    /**
     * @var string
     */
    public const OPENID_ASSOC_HANDLE = 'openid.assoc_handle';

    /**
     * @var string
     */
    public const OPENID_NS = 'http://specs.openid.net/auth/2.0';

    /**
     * @var string
     */
    public const OPENID_ERROR = 'openid.error';

    /**
     * @var string
     */
    public const OPENID_RETURN_TO = 'openid.return_to';

    /**
     * @var string
     */
    public const OPENID_CLAIMED_ID = 'openid.claimed_id';

    /**
     * Fields Steam signs on every assertion.
     */
    public const REQUIRED_SIGNED_FIELDS = ['op_endpoint', 'claimed_id', 'identity', 'return_to', 'response_nonce', 'assoc_handle'];

    /**
     * How far the response nonce timestamp may drift from now, in seconds.
     */
    public const NONCE_MAX_AGE = 300;

    protected ?Repository $nonceCache = null;

    /**
     * {@inheritdoc}
     */
    protected $stateless = true;

    protected function getAuthUrl($state): string
    {
        return $this->buildUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if (! $this->validate()) {
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
        if ($token === null) {
            return null;
        }

        if (empty($this->clientSecret)) {
            throw new RuntimeException('The Steam API key has not been specified.');
        }

        $response = $this->getHttpClient()->get(
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
        return (new User)->setRaw($user)->map([
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
            'openid.realm'      => sprintf('%s://%s', $this->getScheme(), $realm),
            'openid.identity'   => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ];

        return self::OPENID_URL.'?'.http_build_query($params, '', '&');
    }

    /**
     * Checks the steam login.
     *
     * @return bool
     *
     * @throws OpenIDValidationException
     */
    public function validate()
    {
        $this->normalizeOpenidKeys();

        if (! $this->requestIsValid()) {
            throw new OpenIDValidationException('A critical openid parameter is missing from the request');
        }

        $this->validateAssertion();

        $returnTo = (string) $this->getOrNull(self::OPENID_RETURN_TO);

        if (! $this->validateReturnTo($returnTo)) {
            throw new OpenIDValidationException('Invalid return_to');
        }

        if (! $this->validateHost($returnTo)) {
            throw new OpenIDValidationException('Invalid return_to host');
        }

        $this->claimNonce();

        $requestOptions = $this->getDefaultRequestOptions();
        $customOptions = $this->getCustomRequestOptions();

        if (! empty($customOptions) && is_array($customOptions)) {
            $requestOptions = array_merge($requestOptions, $customOptions);
        }

        $response = $this->getHttpClient()->post(self::OPENID_URL, $requestOptions);

        $results = $this->parseResults((string) $response->getBody());

        $isValid = ($results['ns'] ?? null) === self::OPENID_NS
            && ($results['is_valid'] ?? null) === 'true';

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
            && $this->request->has(self::OPENID_SIG)
            && $this->request->has(self::OPENID_RETURN_TO);
    }

    private function getOrNull(string $key): ?string
    {
        return Arr::get($this->request->all(), $key);
    }

    /**
     * Normlize openid keys from diffrent requests
     *
     * @return void
     */
    private function normalizeOpenidKeys()
    {
        $normalized = $this->request->collect()->mapWithKeys(function ($value, $key) {
            return [preg_replace('/^openid_/', 'openid.', $key) => $value];
        })->all();

        $this->request->replace($normalized);
    }

    /**
     * @return array
     */
    public function getDefaultRequestOptions()
    {
        return [
            RequestOptions::FORM_PARAMS => $this->getParams(),
            RequestOptions::PROXY       => $this->getConfig('proxy'),
            RequestOptions::HEADERS     => $this->getHeaders(),
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
            'openid.assoc_handle' => $this->getOrNull(self::OPENID_ASSOC_HANDLE),
            'openid.signed'       => $this->getOrNull(self::OPENID_SIGNED),
            'openid.sig'          => $this->getOrNull(self::OPENID_SIG),
            'openid.ns'           => self::OPENID_NS,
            'openid.mode'         => 'check_authentication',
            'openid.error'        => $this->getOrNull(self::OPENID_ERROR),
        ];

        $signedParams = explode(',', (string) $this->getOrNull(self::OPENID_SIGNED));

        foreach ($signedParams as $item) {
            $value = $this->getOrNull('openid.'.str_replace('.', '_', $item));
            $params['openid.'.$item] = $value;
        }

        return $params;
    }

    public function getHeaders(): array
    {
        // Without it Steam returns 403 Forbidden
        return [
            'referer' => 'https://steamcommunity.com/',
            'origin'  => 'https://steamcommunity.com',
        ];
    }

    /**
     * Parse openID response to an array.
     *
     * @param  string  $results  openid response body
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
            (string) $this->getOrNull(self::OPENID_CLAIMED_ID),
            $matches
        );

        $this->steamId = isset($matches[1]) && is_numeric($matches[1]) ? $matches[1] : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code) {}

    protected function getTokenUrl() {}

    public static function additionalConfigKeys(): array
    {
        return ['realm', 'proxy', 'allowed_hosts', 'force_https', 'nonce_cache_store'];
    }

    /**
     * Checks the assertion fields before asking Steam to verify the signature,
     * so Steam's answer only covers an assertion issued by Steam for this login.
     *
     * @throws OpenIDValidationException
     */
    protected function validateAssertion(): void
    {
        if ($this->getOrNull('openid.mode') !== 'id_res') {
            throw new OpenIDValidationException('Invalid openid.mode');
        }

        if ($this->getOrNull('openid.ns') !== self::OPENID_NS) {
            throw new OpenIDValidationException('Invalid openid.ns');
        }

        if ($this->getOrNull('openid.op_endpoint') !== self::OPENID_URL) {
            throw new OpenIDValidationException('Invalid openid.op_endpoint');
        }

        $signed = explode(',', (string) $this->getOrNull(self::OPENID_SIGNED));

        if (array_diff(self::REQUIRED_SIGNED_FIELDS, $signed) !== []) {
            throw new OpenIDValidationException('Invalid openid.signed');
        }

        $claimedId = $this->getOrNull(self::OPENID_CLAIMED_ID);

        if ($claimedId !== $this->getOrNull('openid.identity')
            || preg_match('#^https://steamcommunity\.com/openid/id/76561[0-9]{12}/?$#D', (string) $claimedId) !== 1) {
            throw new OpenIDValidationException('Invalid openid.claimed_id');
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z)/', (string) $this->getOrNull('openid.response_nonce'), $nonce) !== 1) {
            throw new OpenIDValidationException('Invalid openid.response_nonce');
        }

        $nonceTime = strtotime($nonce[1]);

        if ($nonceTime === false || abs(time() - $nonceTime) > self::NONCE_MAX_AGE) {
            throw new OpenIDValidationException('Expired openid.response_nonce');
        }
    }

    /**
     * OpenID 2.0 section 11.3: reject a response_nonce that was already accepted.
     * Kept for as long as the nonce could still pass the age check.
     *
     * @throws OpenIDValidationException
     */
    protected function claimNonce(): void
    {
        $cache = $this->getNonceCache();

        // add() always fails on the null store, which would reject every login.
        if ($cache->getStore() instanceof NullStore) {
            return;
        }

        $key = 'socialite:steam-nonce:'.hash('sha256', (string) $this->getOrNull('openid.response_nonce'));

        if (! $cache->add($key, true, self::NONCE_MAX_AGE * 2)) {
            throw new OpenIDValidationException('Replayed openid.response_nonce');
        }
    }

    public function setNonceCache(Repository $cache): static
    {
        $this->nonceCache = $cache;

        return $this;
    }

    protected function getNonceCache(): Repository
    {
        return $this->nonceCache ??= Cache::store($this->getConfig('nonce_cache_store'));
    }

    /**
     * The signed return_to must be exactly the callback URL we sent to Steam.
     * A host-only check can be fooled by URLs that PHP and browsers parse
     * differently, letting an assertion issued for another URL be replayed here.
     */
    protected function validateReturnTo(string $url): bool
    {
        return ! empty($this->redirectUrl) && hash_equals((string) $this->redirectUrl, $url);
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

    protected function getScheme(): string
    {
        if ($this->getConfig('force_https')) {
            return 'https';
        }

        return $this->request->getScheme();
    }
}
