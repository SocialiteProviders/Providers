<?php

namespace App\Providers\Socialite\TikTokShop;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TikTokShopExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('tiktokshop', Provider::class);
    }
}
