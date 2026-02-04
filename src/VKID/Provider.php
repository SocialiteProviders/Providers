<?php

namespace SocialiteProviders\VKID;

use RuntimeException;
use Illuminate\Support\Arr;
use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'VKID';

    /**
     * {@inheritdoc}
     */
    protected $stateless = false;

    /**
     * {@inheritdoc}
     */
    protected $usesPKCE = true;


    /**
     * {@inheritdoc}
     */
    protected $scopes = ['email'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://id.vk.ru/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://id.vk.ru/oauth2/auth';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post('https://id.vk.ru/oauth2/user_info', [
            RequestOptions::HEADERS => ['Accept' => 'application/json'],
            RequestOptions::FORM_PARAMS => [
                'access_token' => is_array($token) ? $token['access_token'] : $token,
                'client_id' => $this->clientId,
            ],
        ]);

        $contents = (string) $response->getBody();

        $response = json_decode($contents, true);

        if (!is_array($response) || !isset($response['user'])) {
            throw new RuntimeException(sprintf(
                'Invalid JSON response from VK: %s',
                $contents
            ));
        }

        return $response['user'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => Arr::get($user, 'user_id'),
            'name'     => trim(Arr::get($user, 'first_name') . ' ' . Arr::get($user, 'last_name')),
            'email'    => Arr::get($user, 'email'),
            'avatar'   => Arr::get($user, 'avatar'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
            'device_id'  => $this->getDeviceId(),
        ]);
    }

    /**
     * Get the device_id from the request.
     *
     * @return string
     */
    protected function getDeviceId()
    {
        return $this->request->input('device_id');
    }
}
