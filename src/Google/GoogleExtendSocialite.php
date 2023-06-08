<?php

namespace SocialiteProviders\Google;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GoogleExtendsSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('google', Provider::class);
    }
}
