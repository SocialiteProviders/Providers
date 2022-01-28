<?php

namespace SocialiteProviders\Minecraft;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MinecraftExtendSocialite
{
    /**
     * Register the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('minecraft', Provider::class);
    }
}
