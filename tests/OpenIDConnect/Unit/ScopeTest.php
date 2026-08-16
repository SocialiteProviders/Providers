<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class ScopeTest extends TestCase
{
    use InteractsWithOidc;

    public function test_configured_scopes_replace_the_defaults(): void
    {
        $provider = $this->makeProvider(['scopes' => ['email']]);

        $this->assertSame(['openid', 'email'], $provider->getScopes());
    }

    public function test_openid_is_always_sent_even_when_not_configured(): void
    {
        $provider = $this->makeProvider(['scopes' => ['email', 'groups']]);

        $this->assertSame(['openid', 'email', 'groups'], $provider->getScopes());
    }

    public function test_scope_strings_split_on_whitespace_and_commas(): void
    {
        $provider = $this->makeProvider(['scopes' => 'email, profile groups']);

        $this->assertSame(['openid', 'email', 'profile', 'groups'], $provider->getScopes());
    }

    public function test_duplicates_are_removed(): void
    {
        $provider = $this->makeProvider(['scopes' => ['openid', 'email', 'email']]);

        $this->assertSame(['openid', 'email'], $provider->getScopes());
    }

    public function test_fluent_scopes_extend_configured_scopes(): void
    {
        $provider = $this->makeProvider(['scopes' => ['email']]);
        $provider->scopes(['offline_access']);

        $this->assertSame(['openid', 'email', 'offline_access'], $provider->getScopes());
    }

    public function test_empty_scope_config_falls_back_to_defaults(): void
    {
        $provider = $this->makeProvider(['scopes' => '']);

        $this->assertSame(['openid', 'email', 'profile'], $provider->getScopes());
    }
}
