<?php

namespace SocialiteProviders\Douban;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DoubanExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('douban', Provider::class);
    }
}
