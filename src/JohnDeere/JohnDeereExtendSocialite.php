<?php

namespace SocialiteProviders\JohnDeere;

use SocialiteProviders\Manager\SocialiteWasCalled;

class JohnDeereExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'john-deere',
            __NAMESPACE__.'\Provider',
            __NAMESPACE__.'\Server'
        );
    }
}