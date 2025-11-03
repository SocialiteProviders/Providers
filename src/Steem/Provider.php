<?php

namespace SocialiteProviders\Steem;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'STEEM';

    protected $scopes = ['login'];

    /**
     * @var string
     */
    protected $domain = 'https://v2.steemconnect.com/';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->domain.'oauth2/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->domain.'api/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        $data = json_decode((string) $response->getBody(), true);

        return Arr::add($data, 'expires_in', Arr::pull($data, 'expires'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->domain.'api/me', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $metadata = json_decode($user['account']['json_metadata'], true);

        return (new User)->setRaw($user)->map([
            'id'          => $user['user'],
            'nickname'    => $user['user'],
            'name'        => Arr::get($metadata, 'profile.name', $user['user']),
            'about'       => Arr::get($metadata, 'profile.about'),
            'location'    => Arr::get($metadata, 'profile.location'),
            'avatar'      => Arr::get($metadata, 'profile.profile_image'),
            'cover_image' => Arr::get($metadata, 'profile.cover_image'),
        ]);
    }
}
