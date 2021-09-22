<?php

namespace SocialiteProviders\Shikimori;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ShikimoriExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('shikimori', Provider::class);
    }
}
