<?php

namespace SocialiteProviders\Okta;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OktaExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('okta', Provider::class);
    }
}
