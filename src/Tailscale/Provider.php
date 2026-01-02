<?php

namespace SocialiteProviders\Tailscale;

use Illuminate\Support\Arr;
use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\User;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Tailscale extends AbstractProvider
{
	const IDENTIFIER = 'TAILSCALE';

	/**
	 * {@inheritdoc}
	 */
	protected $scopes = ['openid', 'profile', 'email'];
	protected $scopeSeparator = ' ';

	public static function additionalConfigKeys()
	{
		return ['base_url'];
	}

	protected function getBaseUrl()
	{
		$baseurl = $this->getConfig('base_url');
		if ($baseurl === null) {
			throw new \InvalidArgumentException('Missing base_url');
		}

		return rtrim($baseurl, '/');
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getAuthUrl($state)
	{
		return $this->buildAuthUrlFromBase($this->getBaseUrl().'/authorize', $state);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getTokenUrl()
	{
		return $this->getBaseUrl().'/token';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getUserByToken($token)
	{
		$response = $this->getHttpClient()->get($this->getBaseUrl().'/userinfo', [
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
		return (new User)->setRaw($user)->map([            
			'id'       => Arr::get($user, 'sub'),
			'email'    => Arr::get($user, 'email'),
			'name'     => Arr::get($user, 'name'), 
			'username' => Arr::get($user, 'username'),
		]);
	}
}
