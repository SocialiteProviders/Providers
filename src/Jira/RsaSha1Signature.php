<?php

namespace SocialiteProviders\Jira;

use Exception;
use GuzzleHttp\Psr7\Uri;
use League\OAuth1\Client\Signature\Signature;

class RsaSha1Signature extends Signature
{
    private string $certPath = '';

    private string $certPassphrase = '';

    /**
     * {@inheritdoc}
     */
    public function method()
    {
        return 'RSA-SHA1';
    }

    /**
     * {@inheritdoc}
     */
    public function sign($uri, array $parameters = [], $method = 'POST')
    {
        $url = $this->createUrl($uri);
        $baseString = $this->baseString($url, $method, $parameters);

        // Fetch the private key cert based on the request
        $certificate = openssl_pkey_get_private("file://$this->certPath", $this->certPassphrase);

        if ($certificate === false) {
            throw new Exception('Cannot get private key.');
        }

        // Pull the private key ID from the certificate
        $privatekeyid = openssl_get_privatekey($certificate);

        // Sign using the key
        openssl_sign($baseString, $signature, $privatekeyid);

        // Release the key resource
        openssl_free_key($privatekeyid);

        return base64_encode($signature);
    }

    public function setCertPath(string $certPath): void
    {
        $this->certPath = $certPath;
    }

    /**
     * Set cert passphrase.
     *
     * @param  $certPassphrase
     */
    public function setCertPassphrase($certPassphrase)
    {
        $this->certPassphrase = $certPassphrase;
    }

    /**
     * Create a Guzzle url for the given URI.
     *
     * @param  string  $uri
     * @return Uri
     */
    protected function createUrl($uri)
    {
        return new Uri($uri);
    }

    /**
     * Generate a base string for a RSA-SHA1 signature
     * based on the given a url, method, and any parameters.
     *
     * @param  Url  $url
     * @param  string  $method
     * @param  array  $parameters
     * @return string
     */
    protected function baseString(Uri $url, $method = 'POST', array $parameters = [])
    {
        $baseString = rawurlencode($method).'&';
        $schemeHostPath = $url->getScheme().'://'.$url->getHost();
        if ($url->getPort() !== null) {
            $schemeHostPath .= ':'.$url->getPort();
        }
        if ($url->getPath() !== '') {
            $schemeHostPath .= $url->getPath();
        }
        $baseString .= rawurlencode($schemeHostPath).'&';

        $data = [];
        parse_str($url->getQuery(), $query);
        foreach (array_merge($query, $parameters) as $key => $value) {
            $data[rawurlencode($key)] = rawurlencode($value);
        }

        ksort($data);
        array_walk($data, function (&$value, $key) {
            $value = $key.'='.$value;
        });
        $baseString .= rawurlencode(implode('&', $data));

        return $baseString;
    }
}
