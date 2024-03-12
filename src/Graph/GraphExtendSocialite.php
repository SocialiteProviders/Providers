<?php

namespace SocialiteProviders\Graph;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GraphExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('graph', Provider::class);
    }
}
