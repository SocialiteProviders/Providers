<?php

namespace SocialiteProviders\Zettle;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ZettleExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('zettle', Provider::class);
    }
}
