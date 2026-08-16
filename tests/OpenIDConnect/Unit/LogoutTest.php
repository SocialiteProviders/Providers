<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use Illuminate\Http\Request;
use InvalidArgumentException;
use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class LogoutTest extends TestCase
{
    use InteractsWithOidc;

    public function test_logout_builds_an_end_session_redirect(): void
    {
        $request = $this->redirectRequest();
        $provider = $this->makeProvider(
            ['post_logout_redirect_uri' => 'https://app.test/goodbye'],
            [$this->jsonResponse($this->discoveryDocument())],
            $request,
        );

        $url = $provider->logout('the-id-token')->getTargetUrl();
        $query = $this->queryOf($url);

        $this->assertStringStartsWith(static::$opBaseUrl.'/logout?', $url);
        $this->assertSame('the-id-token', $query['id_token_hint']);
        $this->assertSame(static::$opClientId, $query['client_id']);
        $this->assertSame('https://app.test/goodbye', $query['post_logout_redirect_uri']);
        $this->assertSame($request->session()->get('logout_state'), $query['state']);
    }

    public function test_an_explicit_post_logout_uri_overrides_the_config(): void
    {
        $provider = $this->makeProvider(
            ['post_logout_redirect_uri' => 'https://app.test/goodbye'],
            [$this->jsonResponse($this->discoveryDocument())],
            $this->redirectRequest(),
        );

        $query = $this->queryOf($provider->logout('t', 'https://app.test/elsewhere')->getTargetUrl());

        $this->assertSame('https://app.test/elsewhere', $query['post_logout_redirect_uri']);
    }

    public function test_logout_without_an_advertised_end_session_endpoint_is_refused(): void
    {
        $doc = $this->discoveryDocument();
        unset($doc['end_session_endpoint']);

        $provider = $this->makeProvider([], [$this->jsonResponse($doc)], $this->redirectRequest());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('end_session_endpoint');

        $provider->logout();
    }

    public function test_no_state_is_sent_when_there_is_no_session_to_check_it_against(): void
    {
        $provider = $this->makeProvider(
            [],
            [$this->jsonResponse($this->discoveryDocument())],
            $this->redirectRequest(withSession: false),
        );

        $query = $this->queryOf($provider->logout('the-id-token')->getTargetUrl());

        $this->assertArrayNotHasKey('state', $query);
    }

    public function test_the_logout_state_round_trip_validates_once_and_only_once(): void
    {
        $request = $this->redirectRequest();
        $provider = $this->makeProvider([], [$this->jsonResponse($this->discoveryDocument())], $request);

        $url = $provider->logout('the-id-token')->getTargetUrl();
        $state = $this->queryOf($url)['state'];

        // The IdP redirects back with that state, same session.
        $return = Request::create('https://app.test/goodbye', 'GET', ['state' => $state]);
        $return->setLaravelSession($request->session());

        $this->assertTrue($provider->validateLogoutState($return));

        $this->assertFalse($provider->validateLogoutState($return));
    }

    public function test_a_forged_logout_state_fails(): void
    {
        $request = $this->redirectRequest();
        $provider = $this->makeProvider([], [$this->jsonResponse($this->discoveryDocument())], $request);

        $provider->logout('the-id-token');

        $return = Request::create('https://app.test/goodbye', 'GET', ['state' => 'forged']);
        $return->setLaravelSession($request->session());

        $this->assertFalse($provider->validateLogoutState($return));
    }

    public function test_logout_state_validation_without_a_session_is_false(): void
    {
        $provider = $this->makeProvider([], [], $this->redirectRequest(withSession: false));

        $this->assertFalse($provider->validateLogoutState(
            Request::create('https://app.test/goodbye', 'GET', ['state' => 'anything'])
        ));
    }

    public function test_an_empty_state_on_both_sides_does_not_validate(): void
    {
        $provider = $this->makeProvider([], [], $this->redirectRequest());

        $return = Request::create('https://app.test/goodbye', 'GET', ['state' => '']);
        $return->setLaravelSession($this->sessionStore());
        $return->session()->put('logout_state', '');

        $this->assertFalse($provider->validateLogoutState($return));
    }

    public function test_logout_appends_to_an_end_session_endpoint_that_already_carries_a_query(): void
    {
        $provider = $this->makeProvider(
            [],
            [$this->jsonResponse($this->discoveryDocument([
                'end_session_endpoint' => static::$opBaseUrl.'/logout?tenant=legacy',
            ]))],
            $this->redirectRequest(),
        );

        $url = $provider->logout('the-id-token')->getTargetUrl();

        $this->assertSame(1, substr_count($url, '?'));
        $query = $this->queryOf($url);
        $this->assertSame('legacy', $query['tenant']);
        $this->assertSame('the-id-token', $query['id_token_hint']);
    }
}
