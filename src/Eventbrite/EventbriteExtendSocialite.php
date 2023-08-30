<?php

namespace SocialiteProviders\Eventbrite;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EventbriteExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('eventbrite', Provider::class);
    }
}
