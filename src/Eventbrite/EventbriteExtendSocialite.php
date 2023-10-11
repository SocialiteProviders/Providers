<?php

namespace SocialiteProviders\Eventbrite;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EventbriteExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('eventbrite', Provider::class);
    }
}
