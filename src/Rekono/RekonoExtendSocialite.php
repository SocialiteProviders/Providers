<?php

namespace SocialiteProviders\Rekono;

use SocialiteProviders\Manager\SocialiteWasCalled;

class RekonoExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('rekono', Provider::class);
    }
}
