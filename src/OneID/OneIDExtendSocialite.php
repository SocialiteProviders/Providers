<?php

namespace SocialiteProviders\OneID;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OneIDExtendSocialite
{
    public function handle(SocialiteWasCalled $event): void
    {
        $event->extendSocialite('oneid', Provider::class);
    }
}


