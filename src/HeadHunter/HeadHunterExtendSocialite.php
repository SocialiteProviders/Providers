<?php

namespace SocialiteProviders\HeadHunter;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HeadHunterExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('headhunter', Provider::class);
    }
}
