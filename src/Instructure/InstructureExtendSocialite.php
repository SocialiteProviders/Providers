<?php

namespace SocialiteProviders\Instructure;

use SocialiteProviders\Manager\SocialiteWasCalled;

class InstructureExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('instructure', Provider::class);
    }
}
