<?php

namespace SocialiteProviders\HeadHunter;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HeadHunterExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('headhunter', Provider::class);
    }
}
