<?php

namespace SocialiteProviders\Naver;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class NaverProvider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'NAVER';

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     *
     * @return string
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://nid.naver.com/oauth2.0/authorize', $state);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        return 'https://nid.naver.com/oauth2.0/token';
    }

    /**
     * Get the access token for the given code.
     *
     * @param string $code
     *
     * @return string
     */
    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->request('POST', $this->getTokenUrl(), [
            'headers'     => ['Accept' => 'application/json'],
            'form_params' => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode($response->getBody(), true);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param string $code
     *
     * @return array
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param string $token
     *
     * @return array
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->request('GET', 'https://openapi.naver.com/v1/nid/getUserProfile.xml', [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        return $this->parseXML($response->getBody())['response'];
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param array $user
     *
     * @return \Laravel\Socialite\User
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => array_key_exists('id', $user) ? $user['id'] : null,
            'name'     => array_key_exists('name', $user) ? $user['name'] : null,
            'nickname' => array_key_exists('nickname', $user) ? $user['nickname'] : null,
            'email'    => array_key_exists('email', $user) ? $user['email'] : null,
            'avatar'   => array_key_exists('profile_image', $user) ? $user['profile_image'] : null,
        ]);
    }

    /**
     * XML -> array 형식 변환.
     *
     * @param string $data
     *
     * @return array
     */
    private function parseXML($data)
    {
        return json_decode(json_encode(simplexml_load_string($data, null, LIBXML_NOCDATA)), true);
    }
}
