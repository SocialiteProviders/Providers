<?php

namespace SocialiteProviders\Ivao;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'IVAO';

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * The scopes being requested that are mandatory.
     *
     * @var array
     */
    protected $requiredScopes = ['email'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://sso.ivao.aero/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.ivao.aero/v2/oauth/token';
    }

    /**
     * {@inheritDoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.ivao.aero/v2/users/me',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritDoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'           => Arr::get($user, 'id'),
            'name'         => Arr::get($user, 'firstName').' '.Arr::get($user, 'lastName'),
            'email'        => Arr::get($user, 'email'),
            'nickname'     => Arr::get($user, 'publicNickname'),
            'division'     => Arr::get($user, 'divisionId'),
            'atc_rating'   => Arr::get($user, 'rating.atcRating.id'),
            'pilot_rating' => Arr::get($user, 'rating.pilotRating.id'),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getScopes()
    {
        return array_unique(array_merge(parent::getScopes(), $this->getRequiredScopes()));
    }

    /**
     * {@inheritDoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);

        if ($requiredScopes = $this->getRequiredScopes()) {
            $fields['required_scopes'] = $this->formatScopes($requiredScopes, $this->scopeSeparator);
        }

        return $fields;
    }

    /**
     * Merge the required scopes of the requested access.
     *
     * @param  array|string  $scopes
     * @return $this
     */
    public function requiredScopes($scopes)
    {
        $this->requiredScopes = array_unique(array_merge($this->requiredScopes, (array) $scopes));

        return $this;
    }

    /**
     * Set the required scopes of the requested access.
     *
     * @param  array|string  $scopes
     * @return $this
     */
    public function setRequiredScopes($scopes)
    {
        $this->requiredScopes = array_unique((array) $scopes);

        return $this;
    }

    /**
     * Get the current required scopes.
     *
     * @return array
     */
    public function getRequiredScopes()
    {
        return $this->requiredScopes;
    }
}
