<?php

namespace SocialiteProviders\Patreon;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PatreonExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('patreon', Provider::class);
    }
}
