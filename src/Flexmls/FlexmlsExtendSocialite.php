<?php

namespace SocialiteProviders\Flexmls;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FlexmlsExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('flexmls', Provider::class);
    }
}
