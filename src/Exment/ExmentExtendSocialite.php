<?php

namespace SocialiteProviders\Exment;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ExmentExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('exment', Provider::class);
    }
}
