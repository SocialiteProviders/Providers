<?php

namespace SocialiteProviders\Snapchat;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SnapchatExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('snapchat', Provider::class);
    }
}
