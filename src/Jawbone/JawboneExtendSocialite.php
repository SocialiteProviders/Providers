<?php

namespace SocialiteProviders\Jawbone;

use SocialiteProviders\Manager\SocialiteWasCalled;

class JawboneExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'jawbone',
            __NAMESPACE__.'\Provider'
        );
    }
}
