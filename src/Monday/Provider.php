<?php

namespace SocialiteProviders\Monday;

use Laravel\Socialite\Two\AbstractProvider;
use SocialiteProviders\Manager\ConfigTrait;
use SocialiteProviders\Manager\Contracts\OAuth2\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\User;


class Provider extends AbstractProvider implements ProviderInterface
{
    use ConfigTrait;

    /**
     * Unique Provider Identifier.
     */
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
     * @inheritDoc
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://auth.monday.com/oauth2/authorize', $state);
    }

    /**
     * @inheritDoc
     */
    protected function getTokenUrl()
    {
        return 'https://auth.monday.com/oauth2/token';
    }

    /**
     * @inheritDoc
     */
    protected function getUserByToken($token)
    {
        $response = $this->httpClient
            ->post('https://api.monday.com/v2', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => $token,
                ],
                'body' => json_encode([
                    'query' => <<<GQL
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
                ])
            ]);

        return json_decode($response->getBody()->getContents(), true)['data']['me'];
    }

    /**
     * @inheritDoc
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'avatar' => $user['photo_original'],
        ]);
    }
}
