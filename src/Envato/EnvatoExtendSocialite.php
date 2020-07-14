<?php

namespace SocialiteProviders\Envato;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EnvatoExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('envato', Provider::class);
    }
}
