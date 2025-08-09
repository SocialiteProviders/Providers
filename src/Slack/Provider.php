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

    protected array $userScopes = ['identity.basic', 'identity.email', 'identity.team', 'identity.avatar'];

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

    public function setUserScopes($scopes)
    {
        $this->userScopes = (array) $scopes;

        return $this;
    }

    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);

        if ($this->shouldUseOpenIdConnect()) {
            $fields['scope'] = $this->formatScopes($this->userScopes, ' ');
        } else {
            $fields['user_scope'] = $this->formatScopes($this->userScopes, $this->scopeSeparator);
            $fields['scope'] = $this->formatScopes($this->scopes, $this->scopeSeparator);
        }

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

        if ($this->shouldUseOpenIdConnect()) {
            $token = Arr::get($response, 'access_token');
        } else {
            $token = Arr::get($response, 'authed_user.access_token');
        }

        $user = $this->getUserByToken($token);

        /** @var User $userInstance */
        $userInstance = $this->userInstance($response, $user);
        $userInstance->setAccessTokenResponseBody($response);

        return $userInstance;
    }

    protected function mapUserToObject(array $user)
    {
        if ($this->usesOpenIdScopes()) {
            $attributes = [
                'id'              => Arr::get($user, 'sub'),
                'name'            => Arr::get($user, 'name'),
                'email'           => Arr::get($user, 'email'),
                'avatar'          => Arr::get($user, 'picture'),
                'organization_id' => Arr::get($user, 'https://slack.com/team_id'),
            ];
        } else {
            $attributes = [
                'id'              => Arr::get($user, 'user.id'),
                'name'            => Arr::get($user, 'user.name'),
                'email'           => Arr::get($user, 'user.email'),
                'avatar'          => Arr::get($user, 'user.image_512'),
                'organization_id' => Arr::get($user, 'team.id'),
            ];
        }

        return (new User)->setRaw($user)->map($attributes);
    }

    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS     => $this->getTokenHeaders($code),
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    public function getAuthUrl($state): string
    {
        if ($this->shouldUseOpenIdConnect()) {
            $url = 'https://slack.com/openid/connect/authorize';
        } else {
            $url = 'https://slack.com/oauth/v2/authorize';
        }

        return $this->buildAuthUrlFromBase($url, $state);
    }

    protected function getTokenUrl(): string
    {
        if ($this->shouldUseOpenIdConnect()) {
            return 'https://slack.com/api/openid.connect.token';
        }

        return 'https://slack.com/api/oauth.v2.access';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        if ($this->usesOpenIdScopes()) {
            $url = 'https://slack.com/api/openid.connect.userInfo';
        } else {
            $url = 'https://slack.com/api/users.identity';
        }

        $response = $this->getHttpClient()->get($url, [
            RequestOptions::HEADERS => ['Authorization' => 'Bearer '.$token],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function shouldUseOpenIdConnect(): bool
    {
        return $this->usesOpenIdScopes() && empty($this->scopes);
    }

    protected function usesOpenIdScopes(): bool
    {
        $openidScopes = ['openid', 'email', 'profile'];
        $identityScopes = ['identity.basic', 'identity.email', 'identity.team', 'identity.avatar'];

        $hasOpenIdScopes = ! empty(array_intersect($this->userScopes, $openidScopes));
        $hasIdentityScopes = ! empty(array_intersect($this->userScopes, $identityScopes));

        return $hasOpenIdScopes && ! $hasIdentityScopes;
    }
}
