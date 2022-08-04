<?php

namespace SocialiteProviders\Netlify;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NetlifyExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('netlify', Provider::class);
    }
}
