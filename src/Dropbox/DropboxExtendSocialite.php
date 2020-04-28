<?php

namespace SocialiteProviders\Dropbox;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DropboxExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'dropbox',
            __NAMESPACE__.'\Provider'
        );
    }
}
