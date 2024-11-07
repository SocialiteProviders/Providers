<?php

namespace SocialiteProviders\VersionOne;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'VERSIONONE';

    protected $scopes = ['apiv1 query-api-1.0'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://www11.v1host.com/V1Integrations/oauth.v1/auth', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://www11.v1host.com/V1Integrations/oauth.v1/token';
    }

    /**
     * @param  string  $code
     * @return string
     */
    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode((string) $response->getBody(), true);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        try {
            $data = json_encode([
                'from'   => 'Member',
                'select' => ['Name', 'Username', 'Email', 'Avatar.Content'],
                'where'  => ['IsSelf' => 'true'],
            ]);

            $requestOptions = [
                RequestOptions::HEADERS => [
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
                RequestOptions::BODY => $data,
            ];

            $response = $this->getHttpClient()->post(
                'https://www11.v1host.com/V1Integrations/query.v1',
                $requestOptions
            );
        } catch (BadResponseException $e) {
            echo $e->getMessage().PHP_EOL;
        }

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        if (empty($user[0][0])) {
            echo 'Error response user data';
        }

        $user = $user[0][0];

        return (new User)->setRaw($user)->map([
            'id'       => str_replace('Member:', '', $user['_oid']),
            'nickname' => $user['Username'], 'name' => $user['Name'],
            'email'    => $user['Email'], 'avatar' => Arr::get($user, 'Avatar.Content'),
        ]);
    }
}
