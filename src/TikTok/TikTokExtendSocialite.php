<?php

namespace SocialiteProviders\TikTok;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TikTokExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('tiktok', Provider::class);
    }
}
