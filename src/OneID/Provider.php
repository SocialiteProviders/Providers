<?php

namespace SocialiteProviders\OneID;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider
{
   public const IDENTIFIER = 'ONEID';

    protected string $scope = 'one_code';

    /**
     * @throws GuzzleException
     */
    public function logout(string $accessTokenOrSessionId): void
    {
        $this->getHttpClient()->post($this->getBaseUrl() . '/sso/oauth/Authorization.do', [
            RequestOptions::FORM_PARAMS => [
                'grant_type'    => 'one_log_out',
                'client_id'     => $this->getConfig('client_id'),
                'client_secret' => $this->getConfig('client_secret'),
                'access_token'  => $accessTokenOrSessionId,
                'scope'         => $this->getConfig('scope', 'one_code'),
            ],
        ]);
    }

    protected function getBaseUrl(): string
    {
        return rtrim((string) $this->getConfig('base_url', 'https://sso.egov.uz'), '/');
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(rtrim($this->getBaseUrl(), '/') . '/sso/oauth/Authorization.do', $state);
    }

    protected function getTokenUrl(): string
    {
        return rtrim($this->getBaseUrl(), '/') . '/sso/oauth/Authorization.do';
    }

    /**
     * @throws GuzzleException
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => [
                'grant_type' => 'one_access_token_identify',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'access_token' => $token,
                'scope' => $this->getScope(),
            ]
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
        $name = $user['full_name'] ?? trim(implode(' ', array_filter([
            $user['first_name'] ?? null,
            $user['sur_name'] ?? null,
            $user['mid_name'] ?? null,
        ])));

        $legalEntities = [];
        foreach ($user['legal_info'] as $entity) {
            $legalEntities[] = new OneIDUserLegalEntity(
                isBasic: (bool)($entity['is_basic'] ?? false),
                tin: (string)($entity['tin'] ?? ''),
                acronUz: (string)($entity['acron_UZ'] ?? ''),
                leTin: (string)($entity['le_tin'] ?? ''),
                leName: (string)($entity['le_name'] ?? ''),
            );
        }

        return (new OneIDUser())->setRaw($user)->map([
            // Standard Socialite fields
            'id' => $user['user_id'] ?? $user['pin'] ?? $user['sess_id'] ?? null,
            'name' => $name ?: null,
            'email' => $user['email'] ?? null,
            'avatar' => $user['avatar'] ?? null,

            // Custom fields (use consistent keys!)
            'pinfl' => $user['pin'] ?? null,                            // citizen PIN/INN
            'sess_id' => $user['sess_id'] ?? null,                      // OneID session id
            'passport' => $user['pport_no'] ?? null,                    // passport number
            'phone' => $user['mob_phone_no'] ?? $user['phone'] ?? null, // prefer mob_phone_no
            'legal_info' => $legalEntities, // user legal entities if exists | item (is_basic = true) => selected entity
        ]);
    }

    protected function getScope(): string
    {
        return (string)($this->getConfig('scope', $this->scope));
    }
}


