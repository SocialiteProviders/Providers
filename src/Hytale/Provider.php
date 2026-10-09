<?php

namespace SocialiteProviders\Hytale;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://accounts.hytale.com/third-party-developer-apps
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'HYTALE';

    /**
     * Hytale matches scopes exactly against what was granted to the client,
     * so only `openid` — the one scope every client is granted — is
     * requested by default. The profile and optional account claims are
     * only returned when their scope was granted to the client and
     * requested through `scopes()`, so they map to null otherwise.
     */
    protected $scopes = ['openid'];

    protected $scopeSeparator = ' ';

    protected $usesPKCE = true;

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://connect.accounts.hytale.com/oauth2/auth', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://connect.accounts.hytale.com/oauth2/token';
    }

    /**
     * Hytale rejects confidential clients that send their secret in the POST
     * body: the credentials travel in an HTTP Basic header instead. Public
     * clients send their client_id in the body, with no secret.
     *
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $fields = $this->getTokenFields($code);

        $options = [
            RequestOptions::HEADERS     => $this->getTokenHeaders($code),
            RequestOptions::FORM_PARAMS => array_diff_key($fields, array_flip(['client_id', 'client_secret'])),
        ];

        if (! empty($this->clientSecret)) {
            $options[RequestOptions::AUTH] = [$this->clientId, $this->clientSecret];
        } else {
            $options[RequestOptions::FORM_PARAMS]['client_id'] = $this->clientId;
        }

        $response = $this->getHttpClient()->post($this->getTokenUrl(), $options);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://connect.accounts.hytale.com/userinfo', [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Hytale never returns an email, name or avatar. The `sub` claim is a
     * stable per-application anonymous identifier, and the game profile
     * (uuid, username) is only returned when the `hytale:profile`
     * scope is granted. The optional `account:*` claims are only returned
     * when their scope was granted to the client and requested, so they
     * map to null otherwise.
     *
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'                => Arr::get($user, 'sub'),
            'nickname'          => Arr::get($user, 'profile.username'),
            'uuid'              => Arr::get($user, 'profile.uuid'),
            'shared_source'     => Arr::get($user, 'shared_source'),
            'game_ownership'    => Arr::get($user, 'game_ownership'),
            'parental_managed'  => Arr::get($user, 'parental_managed'),
            'name'              => null,
            'email'             => null,
            'avatar'            => null,
        ]);
    }
}
