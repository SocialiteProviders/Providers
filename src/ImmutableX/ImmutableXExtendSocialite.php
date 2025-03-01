<?php

namespace SocialiteProviders\ImmutableX;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ImmutableXExtendSocialite
{
    /**
     * Register the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('immutablex', Provider::class);
    }
}
