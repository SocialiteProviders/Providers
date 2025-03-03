<?php

namespace SocialiteProviders\GoHighLevel;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Two\Token;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'GOHIGHLEVEL';

    protected $scopeSeparator = ' ';

    protected $scopes = ['users.readonly'];

    protected bool $sameWindow = true;

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://marketplace.leadconnectorhq.com/oauth/chooselocation', $state);
    }

    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);

        if ($this->sameWindow) {
            $fields['loginWindowOpenMode'] = 'self';
        }

        return $fields;
    }

    protected function getTokenUrl(): string
    {
        return 'https://services.leadconnectorhq.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $userId = $this->credentialsResponseBody['userId'] ?? null;

        if (!$userId) {
            return null;
        }

        try {

            $response = $this->getHttpClient()->get('https://services.leadconnectorhq.com/users/' . $userId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Version' => '2021-07-28',
                ],
            ]);

            return json_decode((string) $response->getBody(), true);
        } catch (ClientException $e) {
            Log::debug('Failed to fetch user information.', ['exception' => (string) $e]);
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $user = array_merge($user, Arr::only($this->credentialsResponseBody, ['locationId', 'companyId', 'approvedLocations', 'planId', 'userType']));

        return (new User)->setRaw($user)->map([
            'name' => Arr::get($user, 'name'),
            'email' => Arr::get($user, 'email'),
            'id' => Arr::get($user, 'id'),
            'nickname' => null,
            'avatar' => null,
        ]);
    }

    /**
     * Acquire a new access token using the refresh token.
     *
     * @see https://highlevel.stoplight.io/docs/integrations/00d0c0ecaa369-get-access-token
     *
     * @param string $refreshToken
     *
     * @return Token
     */
    public function refreshToken($refreshToken)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
            ],
            RequestOptions::FORM_PARAMS => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
        ]);

        $token = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return new Token(
            Arr::get($token, 'access_token'),
            Arr::get($token, 'refresh_token'),
            Arr::get($token, 'expires_in'),
            explode($this->scopeSeparator, Arr::get($token, 'scope', ''))
        );
    }

    public function inSameWindow(bool $sameWindow = true): self
    {
        $this->sameWindow = $sameWindow;

        return $this;
    }
}
