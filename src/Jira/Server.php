<?php

namespace SocialiteProviders\Jira;

use GuzzleHttp\ClientInterface;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use League\OAuth1\Client\Credentials\ClientCredentialsInterface;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Signature\SignatureInterface;
use SocialiteProviders\Manager\OAuth1\Server as BaseServer;

class Server extends BaseServer
{
    public const JIRA_BASE_URL = 'https://example.jira.com';

    private $jiraBaseUrl;
    private $jiraCertPath;
    private $jiraCertPassphrase;
    private $jiraUserDetailsUrl;

    /**
     * Create a new server instance.
     *
     * !! RsaSha1Signature
     *
     * @param ClientCredentialsInterface|array $clientCredentials
     * @param SignatureInterface               $signature
     */
    public function __construct($clientCredentials, SignatureInterface $signature = null)
    {
        // Pass through an array or client credentials, we don't care
        if (is_array($clientCredentials)) {
            $this->jiraBaseUrl = Arr::get($clientCredentials, 'base_uri');

            $this->jiraUserDetailsUrl = Arr::get($clientCredentials, 'user_details_url');

            $this->jiraCertPath = Arr::get($clientCredentials, 'cert_path', $this->getConfig('cert_path', storage_path().'/app/keys/jira.pem'));

            $this->jiraCertPassphrase = Arr::get($clientCredentials, 'cert_passphrase', $this->getConfig('cert_passphrase', ''));

            $clientCredentials = $this->createClientCredentials($clientCredentials);
        } elseif (!$clientCredentials instanceof ClientCredentialsInterface) {
            throw new InvalidArgumentException('Client credentials must be an array or valid object.');
        }

        $this->clientCredentials = $clientCredentials;

        // !! RsaSha1Signature for Jira
        $this->signature = $signature ?: new RsaSha1Signature($clientCredentials);
        $this->signature->setCertPath($this->jiraCertPath);
        $this->signature->setCertPassphrase($this->jiraCertPassphrase);
    }

    /**
     * Retrieves token credentials by passing in the temporary credentials,
     * the temporary credentials identifier as passed back by the server
     * and finally the verifier code.
     *
     * @param TemporaryCredentials $temporaryCredentials
     * @param string               $temporaryIdentifier
     * @param string               $verifier
     *
     * @return TokenCredentials
     */
    public function getTokenCredentials(TemporaryCredentials $temporaryCredentials, $temporaryIdentifier, $verifier)
    {
        if ($temporaryIdentifier !== $temporaryCredentials->getIdentifier()) {
            throw new InvalidArgumentException(
                'Temporary identifier passed back by server does not match that of stored temporary credentials.
                Potential man-in-the-middle.'
            );
        }
        // oauth_verifier must be at the end of the url, this doesn't seem to work otherwise
        $uri = $this->urlTokenCredentials().'?oauth_verifier='.$verifier;
        $bodyParameters = ['oauth_verifier' => $verifier, 'oauth_token' => $temporaryIdentifier];

        $client = $this->createHttpClient();

        $headers = $this->getHeaders($temporaryCredentials, 'POST', $uri, $bodyParameters);

        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';

        try {
            $response = $client->post($uri, ['headers' => $headers], [$postKey => $bodyParameters]);
        } catch (BadResponseException $e) {
            return $this->handleTokenCredentialsBadResponse($e);
        }
        $responseString = (string) $response->getBody();

        return [
            'tokenCredentials'        => $this->createTokenCredentials($responseString),
            'credentialsResponseBody' => $responseString,
        ];
    }

    /**
     * Get JIRA base URL.
     *
     * @return string
     */
    public function getJiraBaseUrl()
    {
        return $this->getConfig('base_uri', self::JIRA_BASE_URL);
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
        $parameters = $this->baseProtocolParameters();

        // without 'oauth_callback'
        $parameters['oauth_signature'] = $this->signature->sign($uri, $parameters, 'POST');

        return $this->normalizeProtocolParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function urlTemporaryCredentials()
    {
        return $this->getJiraBaseUrl().'/plugins/servlet/oauth/request-token?oauth_callback='.
            rawurlencode($this->clientCredentials->getCallbackUri());
    }

    /**
     * {@inheritdoc}
     */
    public function urlAuthorization()
    {
        return $this->getJiraBaseUrl().'/plugins/servlet/oauth/authorize';
    }

    /**
     * {@inheritdoc}
     */
    public function urlTokenCredentials()
    {
        return $this->getJiraBaseUrl().'/plugins/servlet/oauth/access-token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlUserDetails()
    {
        return empty($this->jiraUserDetailsUrl) ? $this->getJiraBaseUrl().'/rest/api/2/myself' : $this->jiraUserDetailsUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return $data['key'];
    }

    /**
     * {@inheritdoc}
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return $data['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return $data['email'];
    }
}
