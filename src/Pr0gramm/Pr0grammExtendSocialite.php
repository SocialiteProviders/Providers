<?php

namespace SocialiteProviders\Pr0gramm;

use SocialiteProviders\Manager\SocialiteWasCalled;

class Pr0grammExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('pr0gramm', Provider::class);
    }
}
