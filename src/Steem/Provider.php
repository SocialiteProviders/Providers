<?php

namespace SocialiteProviders\Steem;

use GuzzleHttp\ClientInterface;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'STEEM';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['login'];

    /**
     * @var string
     */
    protected $domain = 'https://v2.steemconnect.com/';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->domain.'oauth2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->domain.'api/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            $postKey => $this->getTokenFields($code),
        ]);

        $data = [];

        $data = json_decode($response->getBody(), true);

        return Arr::add($data, 'expires_in', Arr::pull($data, 'expires'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->domain.'api/me', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $metadata = json_decode($user['account']['json_metadata'], true);

        return (new User())->setRaw($user)->map([
            'id'          => $user['user'],
            'nickname'    => $user['user'],
            'name'        => array_get($metadata, 'profile.name', $user['user']),
            'about'       => array_get($metadata, 'profile.about'),
            'location'    => array_get($metadata, 'profile.location'),
            'avatar'      => array_get($metadata, 'profile.profile_image'),
            'cover_image' => array_get($metadata, 'profile.cover_image'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
