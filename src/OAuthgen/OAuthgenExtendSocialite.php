<?php

namespace SocialiteProviders\OAuthgen;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OAuthgenExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('oauthgen', Provider::class);
    }
}
