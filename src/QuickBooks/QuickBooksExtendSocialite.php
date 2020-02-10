<?php

namespace SocialiteProviders\QuickBooks;

use SocialiteProviders\Manager\SocialiteWasCalled;

class QuickBooksExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('quickbooks', __NAMESPACE__.'\Provider');
    }
}
