<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use Illuminate\Http\Request;
use InvalidArgumentException;
use ReflectionMethod;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\OpenIDConnect\OpenIDConnectServiceProvider;
use SocialiteProviders\OpenIDConnect\Providers\TelegramProvider;
use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class TelegramProviderTest extends TestCase
{
    use InteractsWithOidc;

    /**
     * Shaped like https://oauth.telegram.org/.well-known/openid-configuration,
     * which advertises no userinfo, end-session or revocation endpoint.
     */
    private function telegramDiscovery(): array
    {
        return array_diff_key($this->discoveryDocument([
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
            'scopes_supported'                      => ['openid', 'phone', 'profile', 'telegram:bot_access'],
        ]), array_flip(['userinfo_endpoint', 'end_session_endpoint', 'revocation_endpoint']));
    }

    /**
     * An id_token as Telegram issues it for the `profile` scope: an opaque
     * `sub`, the numeric user id in `id` (sent as a string), and no email.
     */
    private function telegramClaims(): array
    {
        return array_diff_key($this->idTokenClaims([
            'sub'                => '1234123412341234123',
            'id'                 => '987654321',
            'name'               => 'John Doe',
            'preferred_username' => 'johndoe',
            'picture'            => 'https://t.me/i/userpic/320/johndoe.jpg',
        ]), ['email' => true]);
    }

    private function telegramResponses(array $claims): array
    {
        return [
            $this->jsonResponse($this->telegramDiscovery()),
            $this->tokenEndpointResponse($this->encodeToken($claims), ['scope' => 'openid profile']),
            $this->jsonResponse($this->jwksDocument()),
        ];
    }

    public function test_telegram_pins_its_issuer(): void
    {
        $provider = new TelegramProvider(
            Request::create('https://app.test/login'),
            'the-client',
            'the-secret',
            'https://app.test/callback',
        );

        $provider->setConfig(new Config('the-client', 'the-secret', 'https://app.test/callback', []));

        $this->assertSame(
            'https://oauth.telegram.org',
            (new ReflectionMethod($provider, 'getConfig'))->invoke($provider, 'base_url'),
        );
    }

    public function test_the_shorthand_resolves_to_the_telegram_provider(): void
    {
        $serviceProvider = new OpenIDConnectServiceProvider($this->app);

        $this->assertSame(
            TelegramProvider::class,
            (new ReflectionMethod($serviceProvider, 'resolveProviderClass'))->invoke($serviceProvider, 'telegram', 'telegram'),
        );
    }

    public function test_the_redirect_asks_for_profile_and_never_for_email(): void
    {
        $provider = $this->makeProvider(
            [],
            [$this->jsonResponse($this->telegramDiscovery())],
            $this->redirectRequest(),
            TelegramProvider::class,
        );

        $query = $this->queryOf($provider->redirect()->getTargetUrl());

        $this->assertSame('openid profile', $query['scope']);
    }

    public function test_profile_is_requested_even_when_the_scopes_are_narrowed(): void
    {
        $provider = $this->makeProvider(['scopes' => 'openid phone'], [], $this->redirectRequest(), TelegramProvider::class);

        $this->assertSame(['openid', 'phone', 'profile'], $provider->getScopes());
    }

    public function test_the_user_id_is_the_numeric_telegram_id_not_the_opaque_sub(): void
    {
        $provider = $this->makeProvider([], $this->telegramResponses($this->telegramClaims()), null, TelegramProvider::class);

        $user = $provider->user();

        $this->assertSame('987654321', $user->getId());
        $this->assertSame('johndoe', $user->getNickname());
        $this->assertSame('John Doe', $user->getName());
        $this->assertSame('https://t.me/i/userpic/320/johndoe.jpg', $user->getAvatar());
        $this->assertNull($user->getEmail());
        $this->assertSame('1234123412341234123', $user->getRaw()['sub']);
    }

    public function test_a_missing_email_does_not_trigger_a_userinfo_call(): void
    {
        $provider = $this->makeProvider([], $this->telegramResponses($this->telegramClaims()), null, TelegramProvider::class);

        $provider->user();

        $this->assertNull($this->lastRequestTo('/userinfo'));
        $this->assertCount(3, $this->httpHistory);
    }

    public function test_a_token_without_the_telegram_id_is_rejected(): void
    {
        $claims = $this->telegramClaims();
        unset($claims['id']);

        $provider = $this->makeProvider([], $this->telegramResponses($claims), null, TelegramProvider::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('profile scope');

        $provider->user();
    }
}
