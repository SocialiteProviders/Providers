<?php

namespace SocialiteProviders\GoHighLevel;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GoHighLevelExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('gohighlevel', Provider::class);
    }
}
