<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use PHPUnit\Framework\Attributes\Test;

class ScopesTest extends TestCase
{
    #[Test]
    public function the_defaults_are_used_when_nothing_is_configured(): void
    {
        $this->assertSame(['openid', 'email', 'profile'], $this->oidcProvider()->getScopes());
    }

    #[Test]
    public function a_configured_scope_is_never_repeated(): void
    {
        // Previously produced "openid email profile openid email offline_access",
        // which Azure AD B2C rejects and which is malformed regardless.
        $scopes = $this->oidcProvider(['scopes' => 'openid email offline_access'])->getScopes();

        $this->assertSame(['openid', 'email', 'offline_access'], $scopes);
        $this->assertSame(array_unique($scopes), $scopes);
    }

    #[Test]
    public function a_configured_list_replaces_the_defaults(): void
    {
        // The narrowing case: an OP that does not support `profile`.
        $this->assertSame(['openid', 'email'], $this->oidcProvider(['scopes' => 'email'])->getScopes());
    }

    #[Test]
    public function openid_is_always_present(): void
    {
        $this->assertContains('openid', $this->oidcProvider(['scopes' => 'email'])->getScopes());
        $this->assertSame('openid', $this->oidcProvider(['scopes' => 'groups'])->getScopes()[0]);
    }

    #[Test]
    public function a_scope_string_may_be_separated_by_commas_or_extra_whitespace(): void
    {
        $expected = ['openid', 'email', 'offline_access'];

        $this->assertSame($expected, $this->oidcProvider(['scopes' => 'email,offline_access'])->getScopes());
        $this->assertSame($expected, $this->oidcProvider(['scopes' => "email,  offline_access\n"])->getScopes());
        $this->assertSame($expected, $this->oidcProvider(['scopes' => ' email   offline_access '])->getScopes());
    }

    #[Test]
    public function a_scope_array_is_accepted(): void
    {
        $this->assertSame(
            ['openid', 'email', 'offline_access'],
            $this->oidcProvider(['scopes' => ['email', 'offline_access']])->getScopes()
        );
    }

    #[Test]
    public function fluent_scopes_are_preserved_alongside_a_configured_list(): void
    {
        $provider = $this->oidcProvider(['scopes' => 'email']);
        $provider->scopes(['groups']);

        $this->assertSame(['openid', 'email', 'groups'], $provider->getScopes());
    }

    #[Test]
    public function fluent_scopes_work_without_any_configured_list(): void
    {
        $provider = $this->oidcProvider();
        $provider->scopes(['groups']);

        $this->assertSame(['openid', 'email', 'profile', 'groups'], $provider->getScopes());
    }

    #[Test]
    public function the_authorize_request_carries_the_resolved_scope_string(): void
    {
        $request = $this->request();
        $provider = $this->oidcProvider(['scopes' => 'openid email offline_access'], $request);

        $url = $provider->redirect()->getTargetUrl();
        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

        $this->assertSame('openid email offline_access', $query['scope']);
    }
}
