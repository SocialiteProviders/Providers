<?php

namespace SocialiteProviders\SuperOffice;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SuperOfficeExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('superoffice', Provider::class);
    }
}
