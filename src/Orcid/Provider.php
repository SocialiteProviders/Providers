<?php

namespace SocialiteProviders\Orcid;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'ORCID';

    /**
     * Base URL for ORCID Sandpit Environment.
     */
    public const sandboxURL = 'https://sandbox.orcid.org/';

    /**
     * Base URL for ORCID Production Environment.
     */
    public const productionURL = 'https://orcid.org/';

    /**
     * Profile Data URL for ORCID Sandpit Environment.
     */
    public const sandboxProfileURL = 'https://pub.sandbox.orcid.org/v2.1/';

    /**
     * Profile Data URL for ORCID Production Environment.
     */
    public const productionProfileURL = 'https://pub.orcid.org/v2.1/';

    protected $scopes = ['/authenticate', '/read-limited'];

    protected $scopeSeparator = ' ';

    public static function additionalConfigKeys(): array
    {
        return [
            'environment',
            'uid_fieldname',
        ];
    }

    /**
     * Tests whether we are integrating to the ORCID Sandbox or Production environments
     * Change the value of ORCID_ENVIRONMENT in your .env to switch.
     *
     * @return bool
     */
    protected function useSandbox()
    {
        return $this->getConfig('environment') !== 'production';
    }

    /**
     * Concatenate a base URL for ORCID oAuth requests.
     *
     * @return string
     */
    protected function baseUrl($path)
    {
        return ($this->useSandbox() ? self::sandboxURL : self::productionURL).$path;
    }

    /**
     * Concatenate a base URL for ORCID profile data requests.
     *
     * @return string
     */
    protected function profileUrl($path)
    {
        return ($this->useSandbox() ? self::sandboxProfileURL : self::productionProfileURL).$path;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->baseUrl('oauth/authorize'), $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->baseUrl('oauth/token');
    }

    /**
     * Assuming authentication and token generation succeeeds, return a User object.
     *
     * @return $user Laravel\Socialite\Two\User
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException;
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        $user = $this->mapUserToObject($this->getUserByToken(
            $response
        ));

        $token = Arr::get($response, 'access_token');

        return $user->setToken($token)
            ->setRefreshToken(Arr::get($response, 'refresh_token'))
            ->setExpiresIn(Arr::get($response, 'expires_in'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $orcid = Arr::get($token, 'orcid');
        $accessToken = Arr::get($token, 'access_token');

        $userUrl = $this->profileUrl("{$orcid}/record");
        $response = $this->getHttpClient()->get($userUrl, [
            RequestOptions::HEADERS => [
                'Content-Type'  => 'application/vnd.orcid+xml',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$accessToken,
            ],
        ]);

        $user = json_decode((string) $response->getBody(), true);

        $user['email'] = $this->getEmail($user);

        return $user;
    }

    /**
     * Get the email for the given access token.
     *
     * NOTE: this doesn't alway succeed becuase ORCID gives users the option to keep their email private
     * If your app design relies on fetching the user email from ORCID, you should consider checking
     * that it exists in your LoginController logic.
     *
     * @param  string  $token
     * @return string|null
     */
    protected function getEmail($user)
    {
        foreach ($user['person']['emails']['email'] as $m) {
            if ($m['primary'] === true && $m['verified'] === true) {
                return $m['email'];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $given_name = $user['person']['name']['given-names']['value'] ?? '';
        $family_name = $user['person']['name']['family-name']['value'] ?? '';

        return (new User)->setRaw($user)->map([
            $this->getConfig('uid_fieldname', 'id') => $user['orcid-identifier']['path'],
            'nickname'                              => $given_name,
            'name'                                  => sprintf('%s %s', $given_name, $family_name),
            'email'                                 => Arr::get($user, 'email'),
        ]);
    }

    /**
     * Get the access token for the given code.
     *
     * @param  string  $code
     * @return string
     */
    public function getAccessToken($code)
    {
        $s = $this->scopes[0];
        $data = "client_id={$this->clientId}&client_secret={$this->clientSecret}&grant_type=client_credentials&scope={$s}";

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => ['Accept' => 'application/json', 'Content-Type' => 'application/x-www-form-urlencoded'],
            RequestOptions::BODY    => $data,
        ]);

        return json_decode((string) $response->getBody(), true)['access_token'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'orcid' => 'orcid',
        ]);
    }
}
