<?php

namespace SocialiteProviders\Discord;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DiscordExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('discord', Provider::class);
    }
}
