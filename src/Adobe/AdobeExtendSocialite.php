<?php

namespace SocialiteProviders\Adobe;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AdobeExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param SocialiteWasCalled $event
     */
    public function handle(SocialiteWasCalled $event)
    {
        $event->extendSocialite('adobe', Provider::class);
    }
}
