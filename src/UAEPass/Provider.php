<?php

namespace SocialiteProviders\UAEPass;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://docs.uaepass.ae/guides/authentication/web-application
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'UAEPASS';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['urn:uae:digitalid:profile:general'];

    protected $scopeSeparator = ':';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            $this->getBaseUrl().'/idshub/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
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
        return (new User())->setRaw($user)->map([
            'uuid'          => $user['uuid'],
            'sub'           => $user['sub'],
            'fullnameAR'    => $user['fullnameAR'],
            'firstnameAR'   => $user['firstnameAR'],
            'lastnameAR'    => $user['lastnameAR'],
            'firstnameEN'   => $user['firstnameEN'],
            'lastnameEN'    => $user['lastnameEN'],
            'fullnameEN'    => $user['fullnameEN'],
            'profileType'   => $user['profileType'] ?? null,
            'unifiedID'     => $user['unifiedID'] ?? null,
            'email'         => $user['email'],
            'idn'           => $user['idn'] ?? null,
            'gender'        => $user['gender'] ?? null,
            'mobile'        => $user['mobile'],
            'userType'      => $user['userType'] ?? null,
            'nationalityEN' => $user['nationalityEN'],
            'nationalityAR' => $user['nationalityAR'],
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
            RequestOptions::QUERY   => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function getBaseUrl(): string
    {
        if(env('UAEPASS_ENV', 'staging') == 'staging'){
            return 'https://stg-id.uaepass.ae';
        }elseif(env('UAEPASS_ENV') == 'production'){
            return 'https://id.uaepass.ae';
        }else{
            return 'https://stg-id.uaepass.ae';
        }
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
    public function getLogoutUrl(?string $redirectUri = null)
    {
        $logoutUrl = $this->getBaseUrl().'/idshub/logout';

        return $redirectUri === null ?
            $logoutUrl :
            $logoutUrl.'?'.http_build_query(['post_logout_redirect_uri' => $redirectUri], '', '&', $this->encodingType);
    }
}
