<?php

namespace SocialiteProviders\Slack;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'SLACK';

    protected $scopes = [];

    protected array $userScopes = ['openid', 'profile', 'email'];

    public function scopes($scopes)
    {
        $this->userScopes = array_unique(array_merge($this->userScopes, (array) $scopes));

        return $this;
    }

    public function botScopes($scopes)
    {
        $this->scopes = array_unique(array_merge($this->scopes, (array) $scopes));

        return $this;
    }

    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);

        $fields['user_scope'] = $this->formatScopes($this->userScopes, $this->scopeSeparator);
        $fields['scope'] = $this->formatScopes($this->scopes, $this->scopeSeparator);

        return $fields;
    }

    public function user()
    {
        if ($this->user) {
            return $this->user;
        }

        if ($this->hasInvalidState()) {
            throw new InvalidStateException;
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        $user = $this->getUserByToken(Arr::get($response, 'authed_user.access_token'));

        /** @var User $userInstance */
        $userInstance = $this->userInstance($response, $user);
        $userInstance->setAccessTokenResponseBody($response);

        return $userInstance;
    }

    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => Arr::get($user, 'https://slack.com/user_id'),
            'name' => Arr::get($user, 'name'),
            'email' => Arr::get($user, 'email'),
            'avatar' => Arr::get($user, 'https://slack.com/user_image_512'),
            'organization_id' => Arr::get($user, 'https://slack.com/team_id'),
        ]);
    }

    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => $this->getTokenHeaders($code),
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    public function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://slack.com/openid/connect/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://slack.com/api/openid.connect.token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://slack.com/api/openid.connect.userInfo', [
            RequestOptions::HEADERS => ['Authorization' => 'Bearer ' . $token],
        ]);

        return json_decode($response->getBody(), true);
    }
}
