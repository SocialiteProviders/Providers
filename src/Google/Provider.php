<?php

namespace SocialiteProviders\Google;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
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
     * Create a new Google provider instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param string $clientId
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
     * @return GoogleUser
     */
    public function user()
    {
        if ($this->user) {
            return $this->user;
        }
        $jwt = $this->request->credential;
        $certificates = $this->fetchCertificates();
        $decoded = (array) JWT::decode($jwt, $certificates);
        return $this->user = $this->mapUserToObject($decoded)
            ->setOrganization(Arr::get($decoded, 'hd'));
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param  array  $user
     * @return GoogleUser
     */
    protected function mapUserToObject(array $user)
    {
        return (new GoogleUser())->setRaw($user)->map([
            'id' => Arr::get($user, 'sub'),
            'nickname' => Arr::get($user, 'nickname'),
            'name' => Arr::get($user, 'name'),
            'email_verified' => Arr::get($user, 'email_verified'),
            'email' => Arr::get($user, 'email'),
            'avatar' => $avatarUrl = Arr::get($user, 'picture'),
            'avatar_original' => $avatarUrl,
        ]);
    }

    /**
     * Fetches the Google signing certificates for JWTs.
     *
     * @return array
     */
    protected function fetchCertificates(): array
    {
        return collect(json_decode(file_get_contents('https://www.googleapis.com/oauth2/v1/certs'), true))->map(function ($key) {
            return new Key($key, 'RS256');
        })->toArray();
    }

    protected function getAuthUrl($state)
    {
        // TODO: Implement getAuthUrl() method.
    }

    protected function getTokenUrl()
    {
        // TODO: Implement getTokenUrl() method.
    }

    protected function getUserByToken($token)
    {
        // TODO: Implement getUserByToken() method.
    }
}
