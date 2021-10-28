<?php

namespace SocialiteProviders\Linode;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LinodeExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('linode', Provider::class);
    }
}
