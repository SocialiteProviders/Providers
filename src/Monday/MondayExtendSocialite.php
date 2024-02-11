<?php

namespace SocialiteProviders\Monday;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MondayExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('monday', Provider::class);
    }
}
