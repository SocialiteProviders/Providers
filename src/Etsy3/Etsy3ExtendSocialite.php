<?php

namespace SocialiteProviders\Etsy3;

use SocialiteProviders\Manager\SocialiteWasCalled;

class Etsy3ExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('etsy3', Provider::class);
    }
}
