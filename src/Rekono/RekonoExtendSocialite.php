<?php

namespace SocialiteProviders\Rekono;

use SocialiteProviders\Manager\SocialiteWasCalled;

class RekonoExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('rekono', Provider::class);
    }
}
