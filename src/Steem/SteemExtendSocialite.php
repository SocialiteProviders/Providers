<?php

namespace SocialiteProviders\Steem;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SteemExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('steem', Provider::class);
    }
}
