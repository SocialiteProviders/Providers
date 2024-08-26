<?php

namespace SocialiteProviders\Monday;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'MONDAY';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['me:read'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritDoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://auth.monday.com/oauth2/authorize', $state);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTokenUrl()
    {
        return 'https://auth.monday.com/oauth2/token';
    }

    /**
     * {@inheritDoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()
            ->post('https://api.monday.com/v2', [
                RequestOptions::HEADERS => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => $token,
                ],
                RequestOptions::BODY => json_encode([
                    'query' => <<<'GQL'
                        query {
                            me {
                                birthday
                                country_code
                                created_at
                                join_date
                                email
                                enabled
                                id
                                is_admin
                                is_guest
                                is_pending
                                is_view_only
                                location
                                mobile_phone
                                name
                                phone
                                photo_original
                                photo_small
                                photo_thumb
                                photo_thumb_small
                                photo_tiny
                                teams {
                                    id
                                    name
                                    picture_url
                                }
                                time_zone_identifier
                                title
                                url
                                utc_hours_diff
                            }
                        }
GQL
                ]),
            ]);

        return json_decode((string) $response->getBody(), true)['data']['me'];
    }

    /**
     * {@inheritDoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'     => $user['id'],
            'name'   => $user['name'],
            'email'  => $user['email'],
            'avatar' => $user['photo_original'],
        ]);
    }
}
