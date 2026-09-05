<?php

namespace SocialiteProviders\Tests\Apple;

use GuzzleHttp\Client;
use SocialiteProviders\Apple\Provider;

/**
 * Serves Apple's JWKS from a mocked client instead of the network.
 */
class AppleProviderStub extends Provider
{
    private ?Client $jwkHttpClient = null;

    public function setJwkHttpClient(Client $client): void
    {
        $this->jwkHttpClient = $client;
    }

    protected function getJwkHttpClient()
    {
        return $this->jwkHttpClient ?? parent::getJwkHttpClient();
    }
}
