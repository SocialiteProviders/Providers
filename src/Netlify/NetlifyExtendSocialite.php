<?php

namespace SocialiteProviders\Netlify;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NetlifyExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('netlify', Provider::class);
    }
}
