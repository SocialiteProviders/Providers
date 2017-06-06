<?php

namespace SocialiteProviders\QuickBooks;

use SocialiteProviders\Manager\SocialiteWasCalled;

class QuickBooksExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'quickbooks', __NAMESPACE__.'\Provider', __NAMESPACE__.'\Server'
        );
    }
}
