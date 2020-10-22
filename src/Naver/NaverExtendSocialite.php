<?php

namespace SocialiteProviders\Naver;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NaverExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('naver', Provider::class);
    }
}
