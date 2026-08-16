<?php

namespace SocialiteProviders\OpenIDConnect;

use SocialiteProviders\Manager\SocialiteWasCalled;

/**
 * Manual registration of the `openidconnect` driver, for apps that wire
 * SocialiteProviders listeners themselves instead of using this package's
 * service provider.
 */
class OpenIDConnectExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('openidconnect', Provider::class);
    }
}
