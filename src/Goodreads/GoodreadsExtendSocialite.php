<?php

namespace SocialiteProviders\Goodreads;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GoodreadsExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'goodreads', __NAMESPACE__.'\Provider', __NAMESPACE__.'\Server'
        );
    }
}
