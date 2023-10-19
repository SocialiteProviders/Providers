<?php

namespace SocialiteProviders\OAuthgen;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OAuthgenExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('oauthgen', Provider::class);
    }
}
