<?php

namespace SocialiteProviders\LlaveMx;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LlaveMxExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('llavemx', Provider::class);
    }
}