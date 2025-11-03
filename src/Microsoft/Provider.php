<?php

namespace SocialiteProviders\Microsoft;

use Exception;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Microsoft\MicrosoftUser as User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'MICROSOFT';

    /**
     * The tenant id associated with personal Microsoft accounts (services like Xbox, Teams for Life, or Outlook).
     * Note: only reported in JWT ID_TOKENs and not in call's to Graph Organization endpoint.
     */
    public const MS_ENTRA_CONSUMER_TENANT_ID = '9188040d-6c67-4c5b-b112-36a304b66dad';

    /**
     * Default field list to request from Microsoft.
     *
     * @see https://docs.microsoft.com/en-us/graph/permissions-reference#user-permissions
     */
    protected const DEFAULT_FIELDS_USER = [
        'id',
        'displayName',
        'businessPhones',
        'givenName',
        'jobTitle',
        'department',
        'mail',
        'mobilePhone',
        'officeLocation',
        'preferredLanguage',
        'surname',
        'userPrincipalName',
        'employeeId',
    ];

    /**
     * Default tenant field list to request from Microsoft.
     *
     * @see https://docs.microsoft.com/en-us/graph/permissions-reference#user-permissions
     */
    protected const DEFAULT_FIELDS_TENANT = ['id', 'displayName', 'city', 'country', 'countryLetterCode', 'state', 'street', 'verifiedDomains'];

    /**
     * {@inheritdoc}
     * https://learn.microsoft.com/en-us/graph/permissions-overview
     * https://learn.microsoft.com/en-us/entra/identity-platform/scopes-oidc#openid-connect-scopes
     * https://learn.microsoft.com/en-us/entra/identity-platform/id-tokens
     */
    protected $scopes = ['openid', 'profile', 'User.Read'];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return
            $this->buildAuthUrlFromBase(
                sprintf(
                    'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize',
                    $this->getConfig('tenant', 'common')
                ),
                $state
            );
    }

    protected function getTokenUrl(): string
    {
        return sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', $this->getConfig('tenant', 'common'));
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
        $logoutUrl = sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/logout', $this->getConfig('tenant', 'common'));

        return $redirectUri === null ?
            $logoutUrl :
            $logoutUrl.'?'.http_build_query(['post_logout_redirect_uri' => $redirectUri], '', '&', $this->encodingType);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $responseUser = $this->getHttpClient()->get(
            'https://graph.microsoft.com/v1.0/me',
            [
                RequestOptions::HEADERS => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
                RequestOptions::QUERY => [
                    '$select' => implode(',', array_merge(self::DEFAULT_FIELDS_USER, $this->getConfig('fields', []))),
                ],
                RequestOptions::PROXY => $this->getConfig('proxy'),
            ]
        );

        $formattedResponse = json_decode((string) $responseUser->getBody(), true);

        if ($this->getConfig('include_avatar', false)) {
            try {
                $imageSize = $this->getConfig('include_avatar_size', '648x648');
                $responseAvatar = $this->getHttpClient()->get(
                    "https://graph.microsoft.com/v1.0/me/photos/{$imageSize}/\$value",
                    [
                        RequestOptions::HEADERS => [
                            'Accept'        => 'image/jpg',
                            'Authorization' => 'Bearer '.$token,
                        ],
                        RequestOptions::PROXY => $this->getConfig('proxy'),
                    ]
                );

                $formattedResponse['avatar'] = base64_encode($responseAvatar->getBody()->getContents()) ?? null;
            } catch (ClientException) {
                //if exception then avatar does not exist.
                $formattedResponse['avatar'] = null;
            }
        }

        $formattedResponse['tenant'] = null;

        if ($this->getConfig('include_tenant_info', false) && ! $this->isConsumerTenant()) {

            $responseTenant = $this->getHttpClient()->get(
                'https://graph.microsoft.com/v1.0/organization',
                [
                    RequestOptions::HEADERS => [
                        'Accept'        => 'application/json',
                        'Authorization' => 'Bearer '.$token,
                    ],
                    RequestOptions::QUERY => [
                        '$select' => implode(',', array_merge(self::DEFAULT_FIELDS_TENANT, $this->getConfig('tenant_fields', []))),
                    ],
                    RequestOptions::PROXY => $this->getConfig('proxy'),
                ]
            );

            $formattedResponse['tenant'] = json_decode((string) $responseTenant->getBody(), true)['value'][0] ?? null;
        }

        $formattedResponse['roles'] = $this->getRoles();

        return $formattedResponse;
    }

    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS     => ['Accept' => 'application/json'],
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
            RequestOptions::PROXY       => $this->getConfig('proxy'),
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
            'nickname' => null,
            'name'     => $user['displayName'],
            'email'    => $user['userPrincipalName'],
            'avatar'   => Arr::get($user, 'avatar'),

            'businessPhones'    => Arr::get($user, 'businessPhones'),
            'displayName'       => Arr::get($user, 'displayName'),
            'givenName'         => Arr::get($user, 'givenName'),
            'jobTitle'          => Arr::get($user, 'jobTitle'),
            'department'        => Arr::get($user, 'department'),
            'mail'              => Arr::get($user, 'mail'),
            'mobilePhone'       => Arr::get($user, 'mobilePhone'),
            'officeLocation'    => Arr::get($user, 'officeLocation'),
            'preferredLanguage' => Arr::get($user, 'preferredLanguage'),
            'surname'           => Arr::get($user, 'surname'),
            'userPrincipalName' => Arr::get($user, 'userPrincipalName'),
            'employeeId'        => Arr::get($user, 'employeeId'),
            'roles'             => Arr::get($user, 'roles'),

            'tenant' => Arr::get($user, 'tenant'),
        ]);

    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'scope' => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
        ]);
    }

    public static function additionalConfigKeys(): array
    {
        return [
            'tenant',
            'include_tenant_info',
            'include_avatar',
            'include_avatar_size',
            'fields',
            'tenant_fields',
            'proxy',
        ];
    }

    /**
     * Get user's roles from the ID_TOKEN.
     * https://learn.microsoft.com/en-us/entra/identity-platform/optional-claims#configure-groups-optional-claims
     *
     * @return array<string>
     */
    public function getRoles(): array
    {
        if ($idToken = $this->parseIdToken($this->credentialsResponseBody)) {

            $claims = $this->validate($idToken);

        }

        return $claims?->roles ?? [];
    }

    /**
     * Check the ID_TOKEN for tenant details via JWT decode.
     * https://learn.microsoft.com/en-us/entra/identity-platform/optional-claims
     *
     * @return bool
     */
    public function isConsumerTenant(): bool
    {
        if ($idToken = $this->parseIdToken($this->credentialsResponseBody)) {

            $claims = $this->validate($idToken);

            return ($claims?->tid ?? '') === self::MS_ENTRA_CONSUMER_TENANT_ID;
        }

        return false;
    }

    /**
     * When Scope includes 'openid' or 'profile' the ID_TOKEN is made available to us.
     * https://learn.microsoft.com/en-us/entra/identity-platform/id-tokens
     *
     * @param  $body
     * @return array|\ArrayAccess|mixed
     */
    protected function parseIdToken($body)
    {
        return Arr::get($body, 'id_token');
    }

    /**
     * Get public keys to verify id_token from jwks_uri.
     * Retrieves the list of public keys in the JWKS format (JSON Web Key Set)
     *
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getJWTKeys(): array
    {
        $response = $this->getHttpClient()->get($this->getOpenIdConfiguration()->jwks_uri);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Get OpenID Configuration.
     *
     * @return mixed
     *
     * @throws \Laravel\Socialite\Two\InvalidStateException
     */
    private function getOpenIdConfiguration(): mixed
    {
        try {
            // URI Discovery Mechanism for the Provider Configuration URI
            //
            // https://learn.microsoft.com/en-us/entra/identity-platform/v2-protocols-oidc#fetch-the-openid-configuration-document
            //
            $discovery = sprintf('https://login.microsoftonline.com/%s/v2.0/.well-known/openid-configuration', $this->getConfig('tenant', 'common'));

            $response = $this->getHttpClient()->get($discovery);
        } catch (Exception $ex) {
            throw new InvalidStateException("Error on getting OpenID Configuration. {$ex}");
        }

        return json_decode((string) $response->getBody());
    }

    /**
     * Extract algorithm from header, failing that defer to OIDC speced supported algorithms then service's default.
     *
     * @param  $jwtHeader
     * @return string
     */
    private function getTokenSigningAlgorithm($jwtHeader): string
    {
        return $jwtHeader?->alg ?? (string) collect(
            array_merge($this->getOpenIdConfiguration()->id_token_signing_alg_values_supported,
                [$this->getConfig('default_algorithm', 'RS256')])
        )->first();
    }

    /**
     * validate id_token
     * - signature validation using firebase/jwt library.
     * - claims validation
     * iss: MUST match iss = issuer value on metadata.
     * aud: MUST include client_id for this client.
     * exp: MUST time() < exp.
     *
     * @param  string  $idToken
     * @return mixed|\stdClass
     *
     * @throws \Laravel\Socialite\Two\InvalidStateException
     */
    private function validate(string $idToken)
    {
        // https://learn.microsoft.com/en-us/entra/identity-platform/access-token-claims-reference
        try {
            [$headersB64, $payloadB64, $sig] = explode('.', $idToken);
            $jwtHeaders = JWT::jsonDecode(JWT::urlsafeB64Decode($headersB64));

            // decode body without signature check
            // $jwtPayload = JWT::jsonDecode(JWT::urlsafeB64Decode($payloadB64));

            // decode body with signature check
            $alg = $this->getTokenSigningAlgorithm($jwtHeaders);
            $headers = new \stdClass;
            $jwtPayload = JWT::decode($idToken, JWK::parseKeySet($this->getJWTKeys(), $alg), $headers);

            // iss validation -  a security token service (STS) URI
            // Identifies the STS that constructs and returns the token, and the Microsoft Entra tenant of the authenticated user.
            // https://learn.microsoft.com/en-au/entra/identity-platform/access-tokens#multitenant-applications
            $issuer = str_replace('{tenantid}', $jwtPayload->tid, $this->getOpenIdConfiguration()->issuer);
            if (strcmp($iss = $jwtPayload->iss, $issuer)) {
                throw new InvalidStateException('iss on id_token does not match issuer value on the OpenID configuration');
            }

            // aud validation - an Application ID URI or GUID
            // Identifies the intended audience of the token.
            if (! str_contains($jwtPayload->aud, $this->clientId)) {
                throw new InvalidStateException('aud on id_token does not match the client_id for this application');
            }

            // exp validation - int, a Unix timestamp
            // Specifies the expiration time before which the JWT can be accepted for processing.
            if ((int) $jwtPayload->exp < time()) {
                throw new InvalidStateException('id_token is expired');
            }

            return $jwtPayload;

        } catch (Exception $e) {
            throw new InvalidStateException("Error on validating id_token. {$e}");
        }
    }
}
