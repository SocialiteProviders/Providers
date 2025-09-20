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
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('linuxdo', Provider::class);
    }
}
