<?php

namespace SocialiteProviders\EyeEm;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EyeEmExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('eyeem', Provider::class);
    }
}
