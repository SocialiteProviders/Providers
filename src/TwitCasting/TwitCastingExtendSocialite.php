<?php

namespace SocialiteProviders\TwitCasting;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TwitCastingExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('twitcasting', Provider::class);
    }
}
