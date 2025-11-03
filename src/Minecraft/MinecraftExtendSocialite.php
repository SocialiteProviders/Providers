<?php

namespace SocialiteProviders\Minecraft;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MinecraftExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('minecraft', Provider::class);
    }
}
