<?php

namespace SocialiteProviders\VKontakte;

use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    protected $fields = ['uid', 'first_name', 'last_name', 'screen_name', 'photo'];

    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'VKONTAKTE';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['email'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://oauth.vk.com/authorize', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://oauth.vk.com/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $lang = $this->getConfig('lang');
        $lang = $lang ? '&lang='.$lang : '';
        $response = $this->getHttpClient()->get(
            'https://api.vk.com/method/users.get?user_ids='.$token['user_id'].'&fields='.implode(',', $this->fields).$lang.'&https=1'
        );

        $response = json_decode($response->getBody()->getContents(), true)['response'][0];

        return array_merge($response, [
            'email' => array_get($token, 'email'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => array_get($user, 'uid'), 'nickname' => array_get($user, 'screen_name'),
            'name' => trim(array_get($user, 'first_name').' '.array_get($user, 'last_name')),
            'email' => array_get($user, 'email'), 'avatar' => array_get($user, 'photo'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessToken($body)
    {
        return json_decode($body, true);
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        $user = $this->mapUserToObject($this->getUserByToken(
            $token = $this->getAccessTokenResponse($this->getCode())
        ));

        return $user->setToken(array_get($token, 'access_token'));
    }

    /**
     * Set the user fields to request from Vkontakte.
     *
     * @param array $fields
     *
     * @return $this
     */
    public function fields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['lang'];
    }
}
