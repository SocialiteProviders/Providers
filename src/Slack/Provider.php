<?php

namespace SocialiteProviders\Slack;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\SlackProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends SlackProvider
{
    public const IDENTIFIER = 'SLACK';

    protected $scopes = [];

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

    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);

        $fields['user_scope'] = $this->formatScopes($this->userScopes, $this->scopeSeparator);


        if (!empty($this->scopes)) {
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

        $user = $this->getUserByToken(Arr::get($response, 'authed_user.access_token'));

        /** @var User $userInstance */
        $userInstance = $this->userInstance($response, $user);
        $userInstance->setAccessTokenResponseBody($response);

        return $userInstance;
    }

    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => Arr::get($user, 'user.id'),
            'name' => Arr::get($user, 'user.name'),
            'email' => Arr::get($user, 'user.email'),
            'avatar' => Arr::get($user, 'user.image_512'),
            'organization_id' => Arr::get($user, 'team.id'),
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
}
