<?php

namespace SocialiteProviders\Discord;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DiscordExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('discord', Provider::class);
    }
}
