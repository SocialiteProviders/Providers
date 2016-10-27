<?php

namespace SocialiteProviders\Coursera;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CourseraExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('coursera', __NAMESPACE__.'\Provider');
    }
}
