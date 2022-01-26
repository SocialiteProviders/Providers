<?php

namespace SocialiteProviders\TwitCasting;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TwitCastingExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('twitcasting', Provider::class);
    }
}
