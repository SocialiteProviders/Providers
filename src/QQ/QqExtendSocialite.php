<?php

namespace SocialiteProviders\QQ;

use SocialiteProviders\Manager\SocialiteWasCalled;

class QqExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('qq', Provider::class);
    }
}
