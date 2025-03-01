<?php

use PHPUnit\Framework\TestCase;
use SocialiteProviders\ImmutableX\Provider;

class ImmutableXTest extends TestCase
{
    public function testGetAuthUrl()
    {
        $provider = new Provider(request(), 'client-id', 'client-secret', 'http://example.com/callback');
        $authUrl = $provider->getAuthUrl('test-state');

        $this->assertStringContainsString('https://auth.immutable.com/oauth/authorize', $authUrl);
        $this->assertStringContainsString('client_id=client-id', $authUrl);
        $this->assertStringContainsString('state=test-state', $authUrl);
    }
}
