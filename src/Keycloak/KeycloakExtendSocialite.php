<?php

namespace SocialiteProviders\Keycloak;

use SocialiteProviders\Manager\SocialiteWasCalled;

class KeycloakExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('keycloak', Provider::class);
    }
}
