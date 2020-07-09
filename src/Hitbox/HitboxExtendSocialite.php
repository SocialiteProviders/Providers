<?php

namespace SocialiteProviders\Hitbox;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HitboxExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('hitbox', Provider::class);
    }
}
