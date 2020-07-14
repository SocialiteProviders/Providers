<?php

namespace SocialiteProviders\InstagramBasic;

use SocialiteProviders\Manager\SocialiteWasCalled;

class InstagramBasicExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('instagrambasic', Provider::class);
    }
}
