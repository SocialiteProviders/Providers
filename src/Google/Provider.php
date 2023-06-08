<?php

namespace SocialiteProviders\Google;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider
{
    /**
     * The authenticated user instance.
     *
     * @var GoogleUser
     */
    protected $user;

    /**
     * The current request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The client ID.
     *
     * @var string
     */
    protected $clientId;

    /**
     * The decoded JWT payload.
     *
     * @var array
     */
    protected $payload;

    /**
     * Create a new Google provider instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param string                     $clientId
     */
    public function __construct(Request $request, $clientId)
    {
        $this->request = $request;
        $this->clientId = $clientId;
    }

    /**
     * Redirect the user to the authentication page for the provider.
     *
     * @throws \Exception
     */
    public function redirect()
    {
        throw new \RuntimeException('Redirect is deprecated for the new Google Auth, see https://developers.google.com/identity/gsi/web/guides/migration#html_and_javascript');
    }


    /**
     * Get the User instance for the authenticated user.
     *
     * @throws InvalidJwtException
     *
     * @return GoogleUser
     */
    public function user()
    {
        if ($this->user) {
            return $this->user;
        }
        $jwt = $this->request->credential;
        if ($jwt === null) {
            throw new InvalidJwtException('Empty JWT payload');
        }
        if (!$this->verifyJwtSignature($jwt)) {
            throw new InvalidJwtException('Invalid JWT signature');
        }
        if (!in_array(Arr::get($this->payload, 'iss'), ['accounts.google.com', 'https://accounts.google.com'])) {
            throw new InvalidJwtException('Invalid JWT issuer');
        }
        if (Arr::get($this->payload, 'aud') !== $this->clientId) {
            throw new InvalidJwtException('Client ID mismatch');
        }
        if (Arr::get($this->payload, 'exp') < time()) {
            throw new InvalidJwtException('Expired token');
        }
        if (Arr::get($this->payload, 'iat') > time()) {
            throw new InvalidJwtException('Token used before issued');
        }

        return $this->user = $this->mapUserToObject($this->payload)
            ->setOrganization(Arr::get($this->payload, 'hd'));
    }


    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param  array $user
     * @return GoogleUser
     */
    protected function mapUserToObject(array $user)
    {
        return (new GoogleUser())->setRaw($user)->map([
            'id'              => Arr::get($user, 'sub'),
            'nickname'        => Arr::get($user, 'nickname'),
            'name'            => Arr::get($user, 'name'),
            'email_verified'  => Arr::get($user, 'email_verified'),
            'email'           => Arr::get($user, 'email'),
            'avatar'          => $avatarUrl = Arr::get($user, 'picture'),
            'avatar_original' => $avatarUrl,
        ]);
    }

    /**
     * Verifies the signature of the JWT issued, via Google Certs.
     *
     * @param  string  $jwt
     *
     * @return bool
     */
    protected function verifyJwtSignature($jwt)
    {
        $jwtParts = explode('.', $jwt);

        $header = json_decode($this->base64UrlDecode($jwtParts[0]), true);
        $this->payload = json_decode($this->base64UrlDecode($jwtParts[1]), true);
        $signature = $this->base64UrlDecode($jwtParts[2]);

        $keyId = Arr::get($header, 'kid');
        $certificates = $this->fetchCertificates();
        $publicKey = openssl_pkey_get_public($certificates[$keyId]);

        return $this->verifySignature($jwtParts[0].'.'.$jwtParts[1], $signature, $publicKey);
    }

    /**
     * Verify the provided signature using OpenSSL.
     *
     * @param  string         $data
     * @param  string         $signature
     * @param  resource|bool  $publicKey
     * @return bool
     */
    protected function verifySignature($data, $signature, $publicKey): bool
    {
        return openssl_verify($data, $signature, $publicKey , OPENSSL_ALGO_SHA256);
    }

    /**
     * Fetches the Google signing certificates for JWTs.
     *
     * @return array
     */
    protected function fetchCertificates(): array
    {
        return json_decode(file_get_contents('https://www.googleapis.com/oauth2/v1/certs'), true);
    }

    /**
     * Decodes a Base64 URL-encoded string.
     *
     * @param string $data
     *
     * @return string
     */
    protected function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    protected function getAuthUrl($state)
    {
        // TODO: Refactor manager package to support non-oauth2 providers
    }

    protected function getTokenUrl()
    {
        // TODO: Refactor manager package to support non-oauth2 providers
    }

    protected function getUserByToken($token)
    {
        // TODO: Refactor manager package to support non-oauth2 providers
    }
}
