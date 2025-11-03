<?php

namespace SocialiteProviders\Vercel;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VercelExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('vercel', Provider::class);
    }
}
