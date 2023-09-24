<?php

namespace SocialiteProviders\Aweber;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AweberExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('aweber', Provider::class, Server::class);
    }
}
