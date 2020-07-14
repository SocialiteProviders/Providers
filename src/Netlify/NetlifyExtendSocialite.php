<?php

namespace SocialiteProviders\Netlify;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NetlifyExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('netlify', Provider::class);
    }
}
