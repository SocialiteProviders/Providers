<?php

namespace SocialiteProviders\Bitrix24;

use SocialiteProviders\Manager\SocialiteWasCalled;

class Bitrix24ExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('bitrix24', Provider::class);
    }
}
