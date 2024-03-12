<?php

namespace SocialiteProviders\Faceit;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FaceitExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('faceit', Provider::class);
    }
}
