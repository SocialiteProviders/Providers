<?php

namespace SocialiteProviders\Pipedrive;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PipedriveExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('pipedrive', Provider::class);
    }
}
