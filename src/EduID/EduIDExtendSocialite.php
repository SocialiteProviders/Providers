<?php

namespace SocialiteProviders\EduID;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EduIDExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('eduid', Provider::class);
    }
}
