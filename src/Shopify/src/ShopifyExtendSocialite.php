<?php

namespace SocialiteProviders\Shopify;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ShopifyExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('shopify', __NAMESPACE__.'\Provider');
    }
}
