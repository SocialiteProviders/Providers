<?php

namespace SocialiteProviders\JobAdder;

use SocialiteProviders\Manager\SocialiteWasCalled;

class JobAdderExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('jobadder', __NAMESPACE__.'\Provider');
    }
}
