<?php

namespace SocialiteProviders\OAuthgen;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OAuthgenExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('oauthgen', Provider::class);
    }
}
