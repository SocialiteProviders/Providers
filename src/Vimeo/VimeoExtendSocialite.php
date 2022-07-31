<?php

namespace SocialiteProviders\Vimeo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VimeoExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('vimeo', Provider::class);
    }
}
