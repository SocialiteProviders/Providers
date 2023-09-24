<?php

namespace SocialiteProviders\Deviantart;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DeviantartExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('deviantart', Provider::class);
    }
}
