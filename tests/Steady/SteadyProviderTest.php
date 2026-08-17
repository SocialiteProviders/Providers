<?php

namespace SocialiteProviders\Tests\Steady;

use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User;
use SocialiteProviders\Steady\Provider;
use SocialiteProviders\Tests\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class SteadyProviderTest extends TestCase
{
    use MatchesSnapshots;

    private const USER_ID = '6f1c2f0e-6d1f-4a3e-9a0b-2b7c8d9e0f11';

    private const ACCESS_TOKEN = 'access-token';

    protected function provider(): string
    {
        return Provider::class;
    }

    public function test_redirect_builds_the_steady_authorize_url(): void
    {
        $url = $this->makeSteadyProvider($this->makeSessionRequest())
            ->redirect()
            ->getTargetUrl();

        $this->assertStringStartsWith('https://steadyhq.com/oauth/authorize?', $url);

        $params = $this->queryParams($url);

        // Random on every call, so it cannot be snapshotted.
        $this->assertNotEmpty($params['state']);
        unset($params['state']);

        $this->assertMatchesJsonSnapshot($params);
    }

    public function test_user_maps_the_steady_profile(): void
    {
        $user = $this->authenticate([
            new Response(200, [], $this->fixture('token.json')),
            new Response(200, [], $this->fixture('user.json')),
        ]);

        $this->assertSame(self::USER_ID, $user->getId());
        $this->assertMatchesJsonSnapshot([
            'id'       => $user->getId(),
            'nickname' => $user->getNickname(),
            'name'     => $user->getName(),
            'email'    => $user->getEmail(),
            'avatar'   => $user->getAvatar(),
        ]);
    }

    public function test_user_name_is_null_when_steady_omits_the_name_attributes(): void
    {
        $user = $this->authenticate([
            new Response(200, [], $this->fixture('token.json')),
            new Response(200, [], $this->fixture('user-without-name.json')),
        ]);

        $this->assertSame(self::USER_ID, $user->getId());
        $this->assertNull($user->getName());
        $this->assertNull($user->getAvatar());
        $this->assertSame('markus@example.com', $user->getEmail());
    }

    public function test_user_carries_the_refresh_token_and_lifetime(): void
    {
        $user = $this->authenticate([
            new Response(200, [], $this->fixture('token.json')),
            new Response(200, [], $this->fixture('user.json')),
        ]);

        $this->assertSame(self::ACCESS_TOKEN, $user->token);
        $this->assertSame('refresh-token', $user->refreshToken);
        $this->assertSame(604800, $user->expiresIn);
        $this->assertSame(['read'], $user->approvedScopes);
    }

    public function test_user_rejects_a_state_that_does_not_match_the_session(): void
    {
        $provider = $this->makeSteadyProvider(
            $this->makeSessionRequest(['code' => 'auth-code', 'state' => 'tampered'], 'expected-state')
        );

        $this->expectException(InvalidStateException::class);

        $provider->user();
    }

    public function test_get_subscription_by_token_returns_the_subscription_resource(): void
    {
        $subscription = $this->makeSteadyProvider(null, [
            new Response(200, [], $this->fixture('subscription.json')),
        ])->getSubscriptionByToken(self::ACCESS_TOKEN);

        $this->assertSame('active', $subscription['attributes']['state']);
        $this->assertMatchesJsonSnapshot($subscription);
    }

    /**
     * A user who never subscribed still gets a 200, with a null data member.
     */
    public function test_get_subscription_by_token_returns_null_without_a_subscription(): void
    {
        $subscription = $this->makeSteadyProvider(null, [
            new Response(200, [], $this->fixture('subscription-none.json')),
        ])->getSubscriptionByToken(self::ACCESS_TOKEN);

        $this->assertNull($subscription);
    }

    public function test_refresh_token_exchanges_against_the_api_token_url(): void
    {
        $token = $this->makeSteadyProvider(null, [
            new Response(200, [], $this->fixture('token.json')),
        ])->refreshToken('refresh-token');

        $this->assertSame(self::ACCESS_TOKEN, $token->token);
        $this->assertSame('refresh-token', $token->refreshToken);
        $this->assertSame(604800, $token->expiresIn);
        $this->assertSame(['read'], $token->approvedScopes);
    }

    /**
     * @param  array<int, Response>  $responses
     */
    private function authenticate(array $responses): User
    {
        return $this->makeSteadyProvider(
            $this->makeSessionRequest(['code' => 'auth-code', 'state' => 'state'], 'state'),
            $responses
        )->user();
    }

    /**
     * @param  array<int, Response>  $responses
     */
    private function makeSteadyProvider(?Request $request = null, array $responses = []): Provider
    {
        /** @var Provider $provider */
        $provider = $this->makeProvider($request, $responses);

        return $provider;
    }

    /**
     * Socialite keeps the CSRF state in the session, so the request needs a store.
     *
     * @param  array<string, mixed>  $query
     */
    private function makeSessionRequest(array $query = [], ?string $state = null): Request
    {
        $request = $this->makeRequest($query);

        $session = new Store('steady-test', new ArraySessionHandler(60));

        if ($state !== null) {
            $session->put('state', $state);
        }

        $request->setLaravelSession($session);

        return $request;
    }
}
