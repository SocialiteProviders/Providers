<?php

namespace SocialiteProviders\Pr0gramm;

use SocialiteProviders\Manager\SocialiteWasCalled;

class Pr0grammExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('pr0gramm', Provider::class);
    }
}
