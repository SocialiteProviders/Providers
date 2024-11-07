<?php

namespace SocialiteProviders\UAEPass;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://docs.uaepass.ae/guides/authentication/web-application
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'UAEPASS';

    protected $scopes = [
        'urn',
        'uae',
        'digitalid',
        'profile',
        'general',
    ];

    protected $scopeSeparator = ':';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/idshub/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getBaseUrl().'/idshub/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            $this->getBaseUrl().'/idshub/userinfo',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
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
            'uuid'          => Arr::get($user, 'uuid'),
            'sub'           => Arr::get($user, 'sub'),
            'fullnameAR'    => Arr::get($user, 'fullnameAR'),
            'firstnameAR'   => Arr::get($user, 'firstnameAR'),
            'lastnameAR'    => Arr::get($user, 'lastnameAR'),
            'firstnameEN'   => Arr::get($user, 'firstnameEN'),
            'lastnameEN'    => Arr::get($user, 'lastnameEN'),
            'fullnameEN'    => Arr::get($user, 'fullnameEN'),
            'profileType'   => Arr::get($user, 'profileType'),
            'unifiedID'     => Arr::get($user, 'unifiedID'),
            'email'         => Arr::get($user, 'email'),
            'idn'           => Arr::get($user, 'idn'),
            'gender'        => Arr::get($user, 'gender'),
            'mobile'        => Arr::get($user, 'mobile'),
            'userType'      => Arr::get($user, 'userType'),
            'nationalityEN' => Arr::get($user, 'nationalityEN'),
            'nationalityAR' => Arr::get($user, 'nationalityAR'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => [
                'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
                'Content-Type'  => 'multipart/form-data',
            ],
            RequestOptions::QUERY => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function getBaseUrl(): string
    {
        return $this->getConfig('base_url');
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        $fields = parent::getTokenFields($code);

        unset($fields['client_id'], $fields['client_secret']);

        return $fields;
    }

    /**
     * Return the logout endpoint with an optional post_logout_redirect_uri query parameter.
     *
     * @param  string|null  $redirectUri  The URI to redirect to after logout, if provided.
     *                                    If not provided, no post_logout_redirect_uri parameter will be included.
     * @return string The logout endpoint URL.
     */
    public function getLogoutUrl(?string $redirectUri = null): string
    {
        $logoutUrl = $this->getBaseUrl().'/idshub/logout';

        return $redirectUri === null ?
            $logoutUrl :
            $logoutUrl.'?'.http_build_query(['post_logout_redirect_uri' => $redirectUri], '', '&', $this->encodingType);
    }

    public static function additionalConfigKeys(): array
    {
        return ['base_url'];
    }
}
