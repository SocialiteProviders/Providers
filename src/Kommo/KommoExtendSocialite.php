<?php

namespace SocialiteProviders\Kommo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class KommoExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     * @return void
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('kommo', Provider::class);
    }
}
