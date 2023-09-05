<?php

namespace SocialiteProviders\Gumroad;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GumroadExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('gumroad', Provider::class);
    }
}
