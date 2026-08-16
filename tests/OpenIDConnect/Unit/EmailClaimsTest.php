<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use InvalidArgumentException;
use ReflectionMethod;
use SocialiteProviders\OpenIDConnect\Providers\EntraProvider;
use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class EmailClaimsTest extends TestCase
{
    use InteractsWithOidc;

    public function test_the_email_claim_is_used_by_default(): void
    {
        $provider = $this->makeProvider([], $this->happyPathResponses());

        $this->assertSame('user@example.com', $provider->user()->getEmail());
    }

    public function test_configured_claims_are_tried_in_order(): void
    {
        $claims = $this->idTokenClaims([
            'email'              => null,
            'preferred_username' => 'login@example.com',
        ]);

        $provider = $this->makeProvider(
            ['email_claims' => ['preferred_username', 'email']],
            $this->happyPathResponses($claims),
        );

        $this->assertSame('login@example.com', $provider->user()->getEmail());
    }

    public function test_an_empty_first_claim_falls_through_to_the_next(): void
    {
        $claims = $this->idTokenClaims([
            'preferred_username' => '',
            'email'              => 'contact@example.com',
        ]);

        $provider = $this->makeProvider(
            ['email_claims' => ['preferred_username', 'email']],
            $this->happyPathResponses($claims),
        );

        $this->assertSame('contact@example.com', $provider->user()->getEmail());
    }

    public function test_claims_can_be_configured_as_a_string(): void
    {
        $claims = $this->idTokenClaims([
            'email' => null,
            'upn'   => 'upn@example.com',
        ]);

        $provider = $this->makeProvider(
            ['email_claims' => 'upn, email'],
            $this->happyPathResponses($claims),
        );

        $this->assertSame('upn@example.com', $provider->user()->getEmail());
    }

    public function test_an_alternate_claim_satisfies_require_email(): void
    {
        $claims = $this->idTokenClaims([
            'email'              => null,
            'preferred_username' => 'login@example.com',
        ]);

        $provider = $this->makeProvider(
            ['require_email' => true, 'email_claims' => ['preferred_username', 'email']],
            $this->happyPathResponses($claims),
        );

        $this->assertSame('login@example.com', $provider->user()->getEmail());
    }

    public function test_userinfo_is_not_consulted_when_an_alternate_claim_is_present(): void
    {
        $claims = $this->idTokenClaims([
            'email'              => null,
            'preferred_username' => 'login@example.com',
        ]);

        $provider = $this->makeProvider(
            ['email_claims' => ['preferred_username', 'email']],
            $this->happyPathResponses($claims),
        );
        $provider->user();

        $this->assertNotContains('GET /userinfo', $this->requestedPaths());
    }

    public function test_require_email_still_fails_when_no_configured_claim_resolves(): void
    {
        $claims = $this->idTokenClaims(['email' => null]);

        $provider = $this->makeProvider(
            ['require_email' => true, 'email_claims' => ['preferred_username', 'email']],
            [
                $this->jsonResponse($this->discoveryDocument()),
                $this->tokenEndpointResponse($this->encodeToken($claims)),
                $this->jsonResponse($this->jwksDocument()),
                $this->jsonResponse(['sub' => 'user-123', 'name' => 'No Email']),
            ],
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no email');

        $provider->user();
    }

    private function entraResolveEmail(array $claims, array $config = []): ?string
    {
        $provider = $this->makeProvider($config, [], providerClass: EntraProvider::class);

        return (new ReflectionMethod($provider, 'resolveEmail'))->invoke($provider, $claims);
    }

    public function test_entra_uses_preferred_username_and_ignores_the_contact_email(): void
    {
        $this->assertSame('login@contoso.com', $this->entraResolveEmail([
            'preferred_username' => 'login@contoso.com',
            'email'              => 'unrelated-contact@gmail.com',
        ]));
    }

    public function test_entra_does_not_fall_back_to_the_spoofable_email_claim(): void
    {
        // A phone-shaped preferred_username with a free-text email claim is
        // the token an attacker-controlled tenant would mint; email must not
        // be consulted unless the app opts in.
        $this->assertNull($this->entraResolveEmail([
            'preferred_username' => '+61400000000',
            'email'              => 'ceo@victim.com',
        ]));

        $this->assertNull($this->entraResolveEmail([
            'email' => 'contact@contoso.com',
        ]));
    }

    public function test_an_entra_login_without_an_acceptable_preferred_username_gets_a_null_email(): void
    {
        $claims = $this->idTokenClaims([
            'preferred_username' => '+61400000000',
            'email'              => 'ceo@victim.com',
        ]);

        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($this->encodeToken($claims)),
            $this->jsonResponse($this->jwksDocument()),
            $this->jsonResponse(['sub' => 'user-123', 'email' => 'ceo@victim.com']),
        ], providerClass: EntraProvider::class);

        $this->assertNull($provider->user()->getEmail());
    }

    public function test_entra_email_fallback_is_opt_in(): void
    {
        $this->assertSame('contact@contoso.com', $this->entraResolveEmail(
            ['email'        => 'contact@contoso.com'],
            ['email_claims' => ['preferred_username', 'email']],
        ));
    }
}
