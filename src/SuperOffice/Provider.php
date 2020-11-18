<?php

namespace SocialiteProviders\SuperOffice;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
	/**
	 * Unique Provider Identifier.
	 */
	public const IDENTIFIER = 'SUPEROFFICE';

	/**
	 * {@inheritdoc}
	 */
	protected $scopes = ['openid'];

	/**
	 * {@inheritdoc}
	 */
	protected $scopeSeparator = ' ';

	/**
	 * {@inheritdoc}
	 */
	protected function getAuthUrl($state): string
	{
		return
			$this->buildAuthUrlFromBase(
				'https://'.($this->config['environment'] ?: 'sod').'.superoffice.com/login/common/oauth/authorize',
				$state
			);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getTokenUrl(): string
	{
		return 'https://'.($this->config['environment'] ?: 'sod').'.superoffice.com/login/common/oauth/tokens';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getTokenFields($code): array
	{
		return array_merge(parent::getTokenFields($code), [
			'grant_type' => 'authorization_code',
		]);
	}

	/**
	 * @param string $token
	 *
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	protected function getUserByToken($token): array
	{
		$response = $this->getHttpClient()->get(
			'https://'.$this->config['environment'].'.superoffice.com/'.$this->config['customer_id'].'/api/v1/User/currentPrincipal',
			     [
			     	'headers' => [
			     		'Accept' => 'application/json',
						'Authorization' => 'Bearer '.$token
					]
				 ]
		);
		return (array)json_decode($response->getBody());
	}

	protected function mapUserToObject(array $user): \Laravel\Socialite\Two\User
	{
		return (new User())->setRaw($user)->map([
			'id' => $user['EjUserId'],
			'name' => $user['FullName'],
			'email' => $user['EMailAddress'],
			'username' => $user['UserName']
		]);
	}

	/**
	 * Add the additional configuration key 'tenant' to enable the branded sign-in experience.
	 *
	 * @return array
	 */
	public static function additionalConfigKeys(): array
	{
		return [
			'environment',
			'customer_id'
		];
	}
}