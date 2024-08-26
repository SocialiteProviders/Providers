<?php

namespace SocialiteProviders\Withings;

use League\OAuth1\Client\Credentials\CredentialsException;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use SocialiteProviders\Manager\OAuth1\Server as BaseServer;
use SocialiteProviders\Manager\OAuth1\User;

class Server extends BaseServer
{
    private string $urlUserDetails = '';

    /**
     * {@inheritdoc}
     */
    public function urlTemporaryCredentials()
    {
        return 'https://oauth.withings.com/account/request_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlAuthorization()
    {
        return 'https://oauth.withings.com/account/authorize';
    }

    /**
     * {@inheritdoc}
     */
    public function urlTokenCredentials()
    {
        return 'https://oauth.withings.com/account/access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlUserDetails()
    {
        return $this->urlUserDetails;
    }

    /**
     * {@inheritdoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        if (isset($data['body']['users'][0])) {
            $data = $data['body']['users'][0];
        }

        $user = new User;

        $user->uid = $data['id'];
        $user->name = $data['firstname'].' '.$data['lastname'];

        // Save all extra data
        $user->extra = [
            'firstName' => $data['firstname'],
            'lastName'  => $data['lastname'],
            'gender'    => $data['gender'],
            'fatmethod' => $data['fatmethod'],
            'birthdate' => $data['birthdate'],
            'shortname' => $data['shortname'],
            'ispublic'  => $data['ispublic'],
        ];

        return $user;
    }

    /**
     * Take the decoded data from the user details URL and extract
     * the user's UID.
     *
     * @param  mixed  $data
     * @param  TokenCredentials  $tokenCredentials
     * @return string|int
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        if (isset($data['body']['users'][0])) {
            $data = $data['body']['users'][0];
        }

        return $data['id'];
    }

    /**
     * {@inheritdoc}
     */
    public function userEmail($data, TokenCredentials $tokenCredentials) {}

    /**
     * {@inheritdoc}
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials) {}

    /**
     * Creates temporary credentials from the body response.
     *
     * @param  string  $body
     * @return TemporaryCredentials
     *
     * @throws CredentialsException
     */
    protected function createTemporaryCredentials($body)
    {
        parse_str($body, $data);

        if (! $data || ! is_array($data)) {
            throw new CredentialsException('Unable to parse temporary credentials response.');
        }

        $temporaryCredentials = new TemporaryCredentials;
        $temporaryCredentials->setIdentifier($data['oauth_token']);
        $temporaryCredentials->setSecret($data['oauth_token_secret']);

        return $temporaryCredentials;
    }

    /**
     * Since Withings has their own unique implementation of oAuth, we need to override
     * the fetchUserDetails-method and add the oauth headers as querystrings.
     *
     * {@inheritdoc}
     */
    protected function fetchUserDetails(TokenCredentials $tokenCredentials, $force = true)
    {
        if (! $this->cachedUserDetailsResponse || $force) {
            // The user-endpoint
            $endpoint = 'https://wbsapi.withings.net/user';

            // Parse the parameters
            $parameters = $this->getOauthParameters($endpoint, $tokenCredentials, [
                'action' => 'getbyuserid',
            ]);

            // Set the urlUserDetails so the parent method can call it via $this->urlUserDetails();
            $this->urlUserDetails = $endpoint.'?'.http_build_query($parameters);
        }

        // Call the parent when we're done
        return parent::fetchUserDetails($tokenCredentials, $force);
    }

    private function getOauthParameters(string $url, TokenCredentials $tokenCredentials, array $extraParams = []): array
    {
        $parameters = array_merge(
            $this->baseProtocolParameters(),
            $this->additionalProtocolParameters(),
            $extraParams,
            [
                'oauth_token' => $tokenCredentials->getIdentifier(),
            ]
        );

        $this->signature->setCredentials($tokenCredentials);

        $parameters['oauth_signature'] = $this->signature->sign($url, $parameters, 'GET');

        return $parameters;
    }
}
