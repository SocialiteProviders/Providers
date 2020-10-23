<?php

namespace SocialiteProviders\Coursera;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CourseraExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('coursera', Provider::class);
    }
}
