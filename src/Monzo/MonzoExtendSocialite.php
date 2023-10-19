<?php

namespace SocialiteProviders\Monzo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MonzoExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('monzo', Provider::class);
    }
}
