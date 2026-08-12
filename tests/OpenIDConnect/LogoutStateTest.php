<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;

/**
 * logout() mints a state; without the matching check on the way back it is
 * decorative rather than protective.
 */
class LogoutStateTest extends TestCase
{
    private function stateFrom(string $url): ?string
    {
        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

        return $query['state'] ?? null;
    }

    #[Test]
    public function a_matching_state_validates(): void
    {
        $request = $this->request();
        $provider = $this->oidcProvider(request: $request);

        $state = $this->stateFrom($provider->logout('id-token')->getTargetUrl());

        $this->assertNotNull($state);
        $this->assertSame($state, $request->session()->get('logout_state'));

        $callback = $this->request(['state' => $state]);
        $callback->session()->put('logout_state', $state);

        $this->assertTrue($provider->validateLogoutState($callback));
    }

    #[Test]
    public function a_mismatched_state_does_not_validate(): void
    {
        $provider = $this->oidcProvider();

        $callback = $this->request(['state' => 'attacker-supplied']);
        $callback->session()->put('logout_state', 'the-real-state');

        $this->assertFalse($provider->validateLogoutState($callback));
    }

    #[Test]
    public function a_missing_returned_state_does_not_validate(): void
    {
        $provider = $this->oidcProvider();

        $callback = $this->request();
        $callback->session()->put('logout_state', 'the-real-state');

        $this->assertFalse($provider->validateLogoutState($callback));
    }

    #[Test]
    public function a_state_with_nothing_stored_does_not_validate(): void
    {
        $provider = $this->oidcProvider();

        $this->assertFalse($provider->validateLogoutState($this->request(['state' => 'anything'])));
    }

    #[Test]
    public function an_empty_state_on_both_sides_does_not_validate(): void
    {
        $provider = $this->oidcProvider();

        $callback = $this->request(['state' => '']);
        $callback->session()->put('logout_state', '');

        $this->assertFalse($provider->validateLogoutState($callback));
    }

    #[Test]
    public function a_state_is_good_for_only_one_round_trip(): void
    {
        $provider = $this->oidcProvider();

        $callback = $this->request(['state' => 'the-state']);
        $callback->session()->put('logout_state', 'the-state');

        $this->assertTrue($provider->validateLogoutState($callback));
        $this->assertFalse($provider->validateLogoutState($callback));
    }

    #[Test]
    public function no_state_is_sent_when_there_is_no_session_to_store_it(): void
    {
        // Emitting one would look like protection that cannot be checked.
        $provider = $this->oidcProvider(request: Request::create(self::REDIRECT, 'GET'));

        $url = $provider->logout('id-token')->getTargetUrl();

        $this->assertNull($this->stateFrom($url));
    }

    #[Test]
    public function validation_fails_without_a_session(): void
    {
        $provider = $this->oidcProvider();

        $this->assertFalse(
            $provider->validateLogoutState(Request::create(self::REDIRECT, 'GET', ['state' => 'x']))
        );
    }

    #[Test]
    public function the_providers_own_request_is_used_by_default(): void
    {
        $request = $this->request(['state' => 'the-state']);
        $request->session()->put('logout_state', 'the-state');

        $this->assertTrue($this->oidcProvider(request: $request)->validateLogoutState());
    }
}
