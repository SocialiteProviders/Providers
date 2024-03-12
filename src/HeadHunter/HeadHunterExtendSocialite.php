<?php

namespace SocialiteProviders\HeadHunter;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HeadHunterExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('headhunter', Provider::class);
    }
}
