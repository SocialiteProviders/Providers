<?php

namespace SocialiteProviders\Usos;

use GuzzleHttp\Exception\BadResponseException;
use League\OAuth1\Client\Credentials\CredentialsException;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use SocialiteProviders\Manager\OAuth1\Server as BaseServer;
use SocialiteProviders\Manager\OAuth1\User;

class Server extends BaseServer
{
    /**
     * {@inheritdoc}
     */
    public function urlTemporaryCredentials()
    {
        return $this->getDomain().'/services/oauth/request_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlAuthorization()
    {
        return $this->getDomain().'/services/oauth/authorize';
    }

    /**
     * {@inheritdoc}
     */
    public function urlTokenCredentials()
    {
        return $this->getDomain().'/services/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlUserDetails()
    {
        return $this->getDomain().'/services/users/user'.'?fields='.$this->getFieldsSelector();
    }

    /*
     * Returns an instance domain, according to the environment configuration
     */
    private function getDomain()
    {
        return $this->getConfig('domain');
    }

    /*
     * Returns a fields selector for services/users/user method, which defines a list of fields,
     * which will be returned from API endpoint.
     */
    private function getFieldsSelector()
    {
        return $this->getConfig('profile_fields_selector', 'id|first_name|last_name|email|photo_urls');
    }

    /**
     * {@inheritdoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        $user = new User();
        $user->id = $data['id'];
        $user->nickname = $data['first_name'].' '.$data['last_name'];
        $user->name = $data['first_name'].' '.$data['last_name'];
        $user->avatar = $data['photo_urls']['50x50'] ?? null;
        $user->email = $data['email'] ?? null;

        $user->extra = $data;

        return $user;
    }

    /**
     * Generate the OAuth protocol header for a temporary credentials
     * request, based on the URI.
     *
     * @param string $uri
     *
     * @return string
     */
    protected function temporaryCredentialsProtocolHeader($uri)
    {
        $parameters = array_merge($this->baseProtocolParameters(), array_merge([
            'oauth_callback' => $this->clientCredentials->getCallbackUri(),

        ], ['scopes' => implode('|', $this->scopes)]));

        $parameters['oauth_signature'] = $this->signature->sign($uri, $parameters, 'POST');

        return $this->normalizeProtocolParameters($parameters);
    }

    public function getTemporaryCredentials()
    {
        $uri = $this->urlTemporaryCredentials();

        $client = $this->createHttpClient();

        $formParams = [
            'scopes' => implode('|', $this->scopes),
        ];

        $header = $this->temporaryCredentialsProtocolHeader($uri);
        $authorizationHeader = ['Authorization' => $header];
        $headers = $this->buildHttpClientHeaders($authorizationHeader);

        try {
            $response = $client->post($uri, [
                'headers'     => $headers,
                'form_params' => $formParams,
            ]);

            return $this->createTemporaryCredentials((string) $response->getBody());
        } catch (BadResponseException $e) {
            $this->handleTemporaryCredentialsBadResponse($e);
        }

        throw new CredentialsException('Failed to get temporary credentials');
    }

    /**
     * {@inheritdoc}
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return $data['id'];
    }

    /**
     * {@inheritdoc}
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return $data['email'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return $data['screen_name'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationUrl($temporaryIdentifier, array $options = [])
    {
        // Somebody can pass through an instance of temporary
        // credentials, and we'll extract the identifier from there.
        if ($temporaryIdentifier instanceof TemporaryCredentials) {
            $temporaryIdentifier = $temporaryIdentifier->getIdentifier();
        }
        $queryOauthToken = ['oauth_token' => $temporaryIdentifier];
        $parameters = (isset($this->parameters))
            ? array_merge($queryOauthToken, $this->parameters)
            : $queryOauthToken;

        $url = $this->urlAuthorization();
        $queryString = http_build_query($parameters);

        return $this->buildUrl($url, $queryString);
    }
}
