<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use PHPUnit\Framework\Attributes\Test;

/**
 * `cache_ttl` gates how long discovery and JWKS responses are held. Resolving
 * it with `?:` treated a deliberate 0 as "unset" and substituted the hour-long
 * default, so anyone turning caching off to debug an IdP kept getting the
 * cached document.
 */
class CacheTtlTest extends TestCase
{
    #[Test]
    public function a_zero_ttl_is_honoured_rather_than_defaulted(): void
    {
        $this->assertSame(0, $this->oidcProvider(['cache_ttl' => 0])->callGetCacheTtl());
    }

    #[Test]
    public function a_zero_ttl_given_as_a_string_is_also_honoured(): void
    {
        // Config that has been through the environment arrives as a string.
        $this->assertSame(0, $this->oidcProvider(['cache_ttl' => '0'])->callGetCacheTtl());
    }

    #[Test]
    public function an_unset_ttl_falls_back_to_an_hour(): void
    {
        $this->assertSame(3600, $this->oidcProvider()->callGetCacheTtl());
    }

    #[Test]
    public function an_empty_ttl_falls_back_to_an_hour(): void
    {
        // An unset environment variable reads as '', which is absence, not 0.
        $this->assertSame(3600, $this->oidcProvider(['cache_ttl' => ''])->callGetCacheTtl());
    }

    #[Test]
    public function a_configured_ttl_is_used(): void
    {
        $this->assertSame(7200, $this->oidcProvider(['cache_ttl' => 7200])->callGetCacheTtl());
        $this->assertSame(7200, $this->oidcProvider(['cache_ttl' => '7200'])->callGetCacheTtl());
    }
}
