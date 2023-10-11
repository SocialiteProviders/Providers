<?php

namespace SocialiteProviders\Snapchat;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SnapchatExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('snapchat', Provider::class);
    }
}
