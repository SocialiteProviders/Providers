<?php

namespace Aslnbxrz\OneID;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'ONEID';

    protected string $scope = 'one_code';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(rtrim($this->getBaseUrl(), '/') . '/sso/oauth/Authorization.do', $state);
    }

    protected function getTokenUrl(): string
    {
        return rtrim($this->getBaseUrl(), '/') . '/sso/oauth/Authorization.do';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => [
                'grant_type' => 'one_access_token_identify',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'access_token' => $token,
                'scope' => $this->getScope(),
            ],
            'headers' => ['Accept' => 'application/json'],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function getCodeFields($state = null): array
    {
        $fields = parent::getCodeFields($state);
        $fields['response_type'] = 'one_code';
        $fields['scope'] = $this->getScope();
        $fields['state'] = $state;
        return $fields;
    }

    protected function getTokenFields($code): array
    {
        $fields = parent::getTokenFields($code);
        $fields['grant_type'] = 'one_authorization_code';
        return $fields;
    }

    protected function mapUserToObject(array $user): OneIDUser
    {
        // Build fallback name if full_name is missing
        $name = $user['full_name'] ?? trim(implode(' ', array_filter([
            $user['first_name'] ?? null,
            $user['sur_name'] ?? null,
            $user['mid_name'] ?? null,
        ])));

        return (new OneIDUser())->setRaw($user)->map([
            // Standard Socialite fields
            'id' => $user['user_id'] ?? $user['pin'] ?? $user['sess_id'] ?? null,
            'name' => $name ?: null,
            'email' => $user['email'] ?? null,
            'avatar' => $user['avatar'] ?? null,

            // Custom fields (use consistent keys!)
            'pinfl' => $user['pin'] ?? null,                      // citizen PIN/INN
            'sess_id' => $user['sess_id'] ?? null,                      // OneID session id
            'passport' => $user['pport_no'] ?? null,                      // passport number
            'phone' => $user['mob_phone_no'] ?? $user['phone'] ?? null, // prefer mob_phone_no
        ]);
    }

    protected function getBaseUrl(): string
    {
        return $this->getConfig('base_url', 'https://sso.egov.uz');
    }

    protected function getScope(): string
    {
        return (string)($this->getConfig('scope', $this->scope));
    }
}


