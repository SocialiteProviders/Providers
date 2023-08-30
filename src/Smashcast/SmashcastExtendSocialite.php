<?php

namespace SocialiteProviders\Smashcast;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SmashcastExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('smashcast', Provider::class);
    }
}
