<?php

namespace SocialiteProviders\MoiKrug;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MoiKrugExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('moikrug', Provider::class);
    }
}
