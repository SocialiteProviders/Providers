<?php

namespace SocialiteProviders\Snapchat;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SnapchatExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('snapchat', __NAMESPACE__.'\Provider');
    }
}
