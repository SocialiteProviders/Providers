<?php

namespace SocialiteProviders\xREL;

use SocialiteProviders\Manager\SocialiteWasCalled;

class xRELExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('xrel', Provider::class);
    }
}
