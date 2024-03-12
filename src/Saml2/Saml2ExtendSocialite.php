<?php

namespace SocialiteProviders\Saml2;

use SocialiteProviders\Manager\SocialiteWasCalled;

class Saml2ExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('saml2', Provider::class);
    }
}
