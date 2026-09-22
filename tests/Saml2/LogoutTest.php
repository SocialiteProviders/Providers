<?php

namespace SocialiteProviders\Tests\Saml2;

use Symfony\Component\HttpFoundation\Response;

class LogoutTest extends TestCase
{
    public function test_it_builds_a_logout_request_to_the_identity_provider(): void
    {
        $provider = $this->makeProvider($this->makeRequest(), ['slo' => static::IDP_SLO_URL]);

        $response = $provider->logoutRequest('user@example.com');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(302, $response->getStatusCode());

        $location = $response->headers->get('Location');
        $this->assertStringStartsWith(static::IDP_SLO_URL, $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $params);
        $this->assertArrayHasKey('SAMLRequest', $params);
        $this->assertNotEmpty($params['SAMLRequest']);
    }
}
