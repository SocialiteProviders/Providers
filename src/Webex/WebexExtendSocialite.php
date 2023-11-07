<?php

namespace SocialiteProviders\Webex;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WebexExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('webex', Provider::class);
    }
}
