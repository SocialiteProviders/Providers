<?php

namespace SocialiteProviders\Webflow;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WebflowExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('webflow', Provider::class);
    }
}
