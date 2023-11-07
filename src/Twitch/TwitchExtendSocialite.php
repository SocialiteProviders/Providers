<?php

namespace SocialiteProviders\Twitch;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TwitchExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('twitch', Provider::class);
    }
}
