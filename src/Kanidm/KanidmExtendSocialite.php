<?php

namespace SocialiteProviders\Kanidm;

use SocialiteProviders\Manager\SocialiteWasCalled;

class KanidmExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('kanidm', Provider::class);
    }
}
