<?php

namespace SocialiteProviders\Discord;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'DISCORD';

    protected $scopes = [
        'identify',
        'email',
    ];

    /**
     * {@inheritdoc}
     */
    protected $consent = false;

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://discord.com/api/oauth2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);

        if (! $this->consent) {
            $fields['prompt'] = 'none';
        }

        return $fields;
    }

    /**
     * Prompt for consent each time or not.
     *
     * @return $this
     */
    public function withConsent()
    {
        $this->consent = true;

        return $this;
    }

    protected function getTokenUrl(): string
    {
        return 'https://discord.com/api/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://discord.com/api/users/@me',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * @param  array  $user
     * @return string|null
     *
     * @see https://discord.com/developers/docs/reference#image-formatting-cdn-endpoints
     */
    protected function formatAvatar(array $user)
    {
        if (empty($user['avatar'])) {
            return null;
        }

        $isGif = preg_match('/a_.+/m', $user['avatar']) === 1;
        $extension = $this->getConfig('allow_gif_avatars', true) && $isGif ? 'gif' :
            $this->getConfig('avatar_default_extension', 'png');

        return sprintf('https://cdn.discordapp.com/avatars/%s/%s.%s', $user['id'], $user['avatar'], $extension);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['username'].($user['discriminator'] !== '0' ? '#'.$user['discriminator'] : ''),
            'name'     => $user['username'],
            'email'    => $user['email'] ?? null,
            'avatar'   => $this->formatAvatar($user),
        ]);
    }

    public static function additionalConfigKeys(): array
    {
        return ['allow_gif_avatars', 'avatar_default_extension'];
    }
}
