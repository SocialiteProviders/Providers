<?php

namespace SocialiteProviders\Foursquare;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FoursquareExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('foursquare', Provider::class);
    }
}
