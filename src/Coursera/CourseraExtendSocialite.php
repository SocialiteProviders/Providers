<?php

namespace SocialiteProviders\Coursera;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CourseraExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('coursera', Provider::class);
    }
}
