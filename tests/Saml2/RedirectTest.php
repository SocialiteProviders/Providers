<?php

namespace SocialiteProviders\Tests\Saml2;

use Symfony\Component\HttpFoundation\Response;

class RedirectTest extends TestCase
{
    public function test_redirect_sends_an_authn_request_to_the_identity_provider(): void
    {
        $request = $this->makeRequest();

        $response = $this->makeProvider($request)->redirect();

        // The binding layer speaks PSR-7 in LightSAML v6; the provider must hand back a
        // Symfony response for the framework to emit.
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(302, $response->getStatusCode());

        $location = $response->headers->get('Location');
        $this->assertStringStartsWith(static::IDP_SSO_URL, $location);

        $params = $this->locationQuery($location);
        $this->assertArrayHasKey('SAMLRequest', $params);
        $this->assertNotEmpty($params['SAMLRequest']);
    }

    public function test_redirect_keeps_the_state_in_the_session_and_relays_it(): void
    {
        $request = $this->makeRequest();

        $response = $this->makeProvider($request)->redirect();

        $params = $this->locationQuery($response->headers->get('Location'));

        $this->assertArrayHasKey('RelayState', $params);
        $this->assertSame($request->session()->get('state'), $params['RelayState']);
        $this->assertNotEmpty($params['RelayState']);
    }

    public function test_stateless_redirect_omits_relay_state(): void
    {
        $request = $this->makeRequest();

        $response = $this->makeProvider($request)->stateless()->redirect();

        $this->assertArrayNotHasKey('RelayState', $this->locationQuery($response->headers->get('Location')));
    }

    /**
     * @return array<string, string>
     */
    private function locationQuery(string $location): array
    {
        parse_str((string) parse_url($location, PHP_URL_QUERY), $params);

        return $params;
    }
}
