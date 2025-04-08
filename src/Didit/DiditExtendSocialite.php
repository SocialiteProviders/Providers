<?php

namespace SocialiteProviders\Didit;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DiditExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     * @return void
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('didit', Provider::class);
    }
}
