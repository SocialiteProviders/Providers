<?php

namespace SocialiteProviders\Kick;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
  const IDENTIFIER = 'KICK';

  protected $usesPKCE = true;

  /**
   * {@inheritdoc}
   * https://docs.kick.com/getting-started/scopes
   */
  protected $scopes = ['user:read'];

  protected $scopeSeparator = ' ';

  /**
   * {@inheritdoc}
   */
  protected function getAuthUrl($state)
  {
    return $this->buildAuthUrlFromBase('https://id.kick.com/oauth/authorize', $state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTokenUrl()
  {
    return 'https://id.kick.com/oauth/token';
  }

  /**
   * {@inheritdoc}
   */
  protected function getUserByToken($token)
  {
    $response = $this->getHttpClient()->get('https://api.kick.com/public/v1/users', [
      RequestOptions::HEADERS => [
        'Authorization' => 'Bearer ' . $token,
      ],
    ]);

    return json_decode((string) $response->getBody(), true);
  }

  /**
   * {@inheritdoc}
   */
  protected function mapUserToObject(array $user)
  {
    $user = $user['data'][0];

    return (new User)->setRaw($user)->map([
      'id' => $user['user_id'],
      'nickname' => $user['name'],
      'email' => $user['email'],
      'avatar' => $user['profile_picture'],
    ]);
  }
}
