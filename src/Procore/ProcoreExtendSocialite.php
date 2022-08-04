<?php

namespace SocialiteProviders\Procore;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ProcoreExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('procore', Provider::class);
    }
}
