<?php

namespace SocialiteProviders\LinuxDo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LinuxDoExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     * @return void
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('linux_do', Provider::class);
    }
}
