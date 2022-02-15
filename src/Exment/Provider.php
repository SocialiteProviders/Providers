<?php

namespace SocialiteProviders\Exment;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'EXMENT';

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['me'];

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['exment_uri'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getBaseUri().'/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUri().'/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUri().'/api/me', [
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
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['value']['user_code'],
            'name'     => $user['value']['user_name'],
            'email'    => $user['value']['email'],
            'avatar'   => null,
        ]);
    }

    /**
     * Get Exment base URI.
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function getBaseUri(): string
    {
        $exmentUri = $this->getConfig('exment_uri');
        if (is_null($exmentUri)) {
            throw new InvalidArgumentException('Please config Exment URI.');
        }

        return rtrim($exmentUri, '/');
    }
}
