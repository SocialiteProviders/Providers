<?php

namespace SocialiteProviders\Shopify;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ShopifyExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('shopify', Provider::class);
    }
}
