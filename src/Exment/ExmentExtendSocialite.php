<?php

namespace SocialiteProviders\Exment;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ExmentExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('exment', Provider::class);
    }
}
