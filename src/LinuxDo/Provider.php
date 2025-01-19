<?php

namespace SocialiteProviders\LinuxDo;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    const IDENTIFIER = 'LINUX_DO';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['profile', 'email'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://connect.linux.do/oauth2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://connect.linux.do/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://connect.linux.do/api/user', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

//        Log::warning("LinuxDO Hint getUserResponse:", ["response"=>$response->getBody()]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
//        Log::warning("LinuxDO Hint UserMap:", $user);
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['username'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'  => $user['avatar_url'],
        ]);
    }
}
