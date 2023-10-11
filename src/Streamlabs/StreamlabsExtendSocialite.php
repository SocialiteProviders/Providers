<?php

namespace SocialiteProviders\Streamlabs;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StreamlabsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('streamlabs', Provider::class);
    }
}
