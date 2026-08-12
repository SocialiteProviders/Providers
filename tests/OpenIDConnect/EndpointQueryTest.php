<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use PHPUnit\Framework\Attributes\Test;

/**
 * Discovered endpoints are not always bare paths. Multi-tenant deployments
 * advertise endpoints that already carry a query, and a hardcoded '?' folds
 * every subsequent parameter into the preceding value.
 */
class EndpointQueryTest extends TestCase
{
    private function queryFrom(string $url): array
    {
        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

        return $query;
    }

    #[Test]
    public function logout_appends_to_an_endpoint_that_already_has_a_query(): void
    {
        $provider = $this->oidcProvider(discovery: $this->discoveryDocument([
            'end_session_endpoint' => self::BASE_URL.'/logout?realm=prod',
        ]));

        $url = $provider->logout('the-id-token', 'https://app.test/bye')->getTargetUrl();

        $this->assertStringNotContainsString('?realm=prod?', $url);

        $query = $this->queryFrom($url);

        // The realm must survive intact rather than swallowing everything.
        $this->assertSame('prod', $query['realm']);
        $this->assertSame('the-id-token', $query['id_token_hint']);
        $this->assertSame('https://app.test/bye', $query['post_logout_redirect_uri']);
        $this->assertSame(self::CLIENT_ID, $query['client_id']);
    }

    #[Test]
    public function logout_still_works_for_a_bare_endpoint(): void
    {
        $provider = $this->oidcProvider();

        $url = $provider->logout('the-id-token')->getTargetUrl();

        $this->assertStringStartsWith(self::BASE_URL.'/logout?', $url);
        $this->assertSame('the-id-token', $this->queryFrom($url)['id_token_hint']);
    }

    #[Test]
    public function the_authorize_endpoint_may_also_carry_a_query(): void
    {
        // The same defect one method away: this one breaks login, not logout.
        $request = $this->request();
        $provider = $this->oidcProvider(
            request: $request,
            discovery: $this->discoveryDocument([
                'authorization_endpoint' => self::BASE_URL.'/authorize?realm=prod',
            ])
        );

        $url = $provider->redirect()->getTargetUrl();

        $this->assertStringNotContainsString('?realm=prod?', $url);

        $query = $this->queryFrom($url);

        $this->assertSame('prod', $query['realm']);
        $this->assertSame(self::CLIENT_ID, $query['client_id']);
        $this->assertSame('code', $query['response_type']);
        $this->assertArrayHasKey('code_challenge', $query);
        $this->assertSame($request->session()->get('state'), $query['state']);
    }

    #[Test]
    public function the_authorize_endpoint_still_works_when_bare(): void
    {
        $provider = $this->oidcProvider();

        $url = $provider->redirect()->getTargetUrl();

        $this->assertStringStartsWith(self::BASE_URL.'/authorize?', $url);
        $this->assertSame('code', $this->queryFrom($url)['response_type']);
    }

    #[Test]
    public function extra_logout_parameters_survive_an_existing_query(): void
    {
        $provider = $this->oidcProvider(discovery: $this->discoveryDocument([
            'end_session_endpoint' => self::BASE_URL.'/logout?realm=prod&tenant=eu',
        ]));

        $url = $provider->logout('the-id-token', null, ['ui_locales' => 'en-GB'])->getTargetUrl();

        $query = $this->queryFrom($url);

        $this->assertSame('prod', $query['realm']);
        $this->assertSame('eu', $query['tenant']);
        $this->assertSame('en-GB', $query['ui_locales']);
    }
}
