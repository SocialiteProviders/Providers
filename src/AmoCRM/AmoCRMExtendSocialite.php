<?php

namespace SocialiteProviders\AmoCRM;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AmoCRMExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('amocrm', Provider::class);
    }
}
