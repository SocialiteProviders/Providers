<?php

namespace SocialiteProviders\Webflow;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://developers.webflow.com/docs/oauth
 */
class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'WEBFLOW';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://webflow.com/oauth/authorize/',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.webflow.com/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.webflow.com/user',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        $body = json_decode((string) $response->getBody(), true);

        return $body['user'] ?? $body;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $name = trim(
            implode(' ', array_filter([
                trim($user['firstName'] ?? ''),
                trim($user['lastName'] ?? ''),
            ]))
        );

        return (new User())->setRaw($user)->map(
            [
                'id'       => $user['_id'] ?? null,
                'nickname' => null,
                'name'     => $name,
                'email'    => $user['email'] ?? null,
                'avatar'   => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $codeFields = parent::getCodeFields($state);

        if (count($this->scopes) === 0) {
            unset($codeFields['scope']);
        }

        return $codeFields;
    }
}
