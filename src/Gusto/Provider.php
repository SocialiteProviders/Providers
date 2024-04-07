<?php

namespace SocialiteProviders\Gusto;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see Gusto OAuth documentation for more details
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'GUSTO';

    /**
     * {@inheritdoc}
     */
    protected $scopes = []; // Specify scopes required by your application, if any.

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        // Gusto's authorization URL
        return $this->buildAuthUrlFromBase(
            'https://api.gusto.com/oauth/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        // Gusto's token endpoint URL
        return 'https://api.gusto.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        // Gusto's endpoint to retrieve user details. Adjust if there's a more specific endpoint.
        $response = $this->getHttpClient()->get(
            'https://api.gusto.com/v1/me',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        // Adjust according to the actual user response from Gusto.
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'], // Ensure this is the correct field for the user's ID.
            'nickname' => $user['name'], // Adjust if necessary.
            'name'     => $user['name'], // Adjust if necessary.
            'email'    => $user['email'], // Ensure this is the correct field for the email.
            // 'avatar'   => $user['avatar'], // Include if Gusto sends user avatar.
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => ['Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret)],
            RequestOptions::QUERY   => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        $fields = parent::getTokenFields($code);

        // Gusto's documentation does not specify removing 'client_id' and 'client_secret' from the body, so we might not need to unset these.
        // Uncomment the following lines if you need to adjust the request for Gusto:
        // unset($fields['client_id'], $fields['client_secret']);

        return $fields;
    }
}
