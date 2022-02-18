<?php

namespace SocialiteProviders\Blackboard;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BlackboardExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('blackboard', Provider::class);
    }
}
