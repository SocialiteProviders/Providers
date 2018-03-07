<?php

namespace SocialiteProviders\Pipedrive;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PipedriveExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('pipedrive', __NAMESPACE__.'\Provider');
    }
}
