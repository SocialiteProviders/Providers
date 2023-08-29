<?php

namespace SocialiteProviders\Kakao;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class KakaoProvider extends AbstractProvider
{
    public const IDENTIFIER = 'KAKAO';

    /**
     * Get the authentication URL for the provider.
     *
     * @param  string  $state
     * @return string
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://kauth.kakao.com/oauth/authorize', $state);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        return 'https://kauth.kakao.com/oauth/token';
    }

    /**
     * Get the access token for the given code.
     *
     * @param  string  $code
     * @return string
     */
    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode((string) $response->getBody(), true);

        return $this->parseAccessToken($this->credentialsResponseBody);
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param  string  $token
     * @return array
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post('https://kapi.kakao.com/v2/user/me', [
            RequestOptions::HEADERS => ['Authorization' => 'Bearer '.$token],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param  array  $user
     * @return \Laravel\Socialite\User
     */
    protected function mapUserToObject(array $user)
    {
        $validEmail = Arr::get($user, 'kakao_account.is_email_valid');
        $verifiedEmail = Arr::get($user, 'kakao_account.is_email_verified');

        return (new User())->setRaw($user)->map([
            'id'        => $user['id'],
            'nickname'  => Arr::get($user, 'properties.nickname'),
            'name'      => Arr::get($user, 'properties.nickname'),
            'email'     => $validEmail && $verifiedEmail ? Arr::get($user, 'kakao_account.email') : null,
            'avatar'    => Arr::get($user, 'properties.profile_image'),
        ]);
    }
}
