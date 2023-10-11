<?php

namespace SocialiteProviders\Shopify;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ShopifyExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('shopify', Provider::class);
    }
}
