<?php

namespace SocialiteProviders\TikTok;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TikTokExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('tiktok', Provider::class);
    }
}
