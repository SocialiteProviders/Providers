<?php

namespace SocialiteProviders\AutodeskAPS;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'AUTODESKAPS';

    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return
            $this->buildAuthUrlFromBase(
                'https://developer.api.autodesk.com/authentication/v2/authorize',
                $state
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://developer.api.autodesk.com/authentication/v2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = [
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUrl,
            'scope'         => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'response_type' => 'code',
        ];

        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        if ($this->usesPKCE()) {
            $fields['code_challenge'] = $this->getCodeChallenge();
            $fields['code_challenge_method'] = $this->getCodeChallengeMethod();
        }

        return array_merge($fields, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        $fields = [
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => $this->redirectUrl,
        ];

        if ($this->usesPKCE()) {
            $fields['code_verifier'] = $this->request->session()->pull('code_verifier');
        }

        return array_merge($fields, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenHeaders($code): array
    {
        $base64 = base64_encode("{$this->clientId}:{$this->clientSecret}");

        return [
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic '.$base64,
        ];
    }

    /**
     * @param  string  $token
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get(
            'https://api.userprofile.autodesk.com/userinfo',
            [
                RequestOptions::HEADERS => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return (array) json_decode((string) $response->getBody(), true);
    }

    /**
     * @see https://aps.autodesk.com/en/docs/oauth/v2/reference/http/userinfo-GET/.
     *
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id'             => $user['sub'],
            'email'          => $user['email'],
            'email_verified' => $user['email_verified'],
            'username'       => $user['preferred_username'],
            'full_name'      => $user['name'],
            'first_name'     => $user['given_name'],
            'last_name'      => $user['family_name'],
            'language'       => $user['locale'],
            'image'          => $user['picture'],
            'website'        => $user['profile'] ?? null,
        ]);
    }
}
