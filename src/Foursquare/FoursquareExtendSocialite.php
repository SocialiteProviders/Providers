<?php

namespace SocialiteProviders\Foursquare;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FoursquareExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('foursquare', Provider::class);
    }
}
