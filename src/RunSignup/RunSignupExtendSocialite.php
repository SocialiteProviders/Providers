<?php

namespace SocialiteProviders\RunSignup;

use SocialiteProviders\Manager\SocialiteWasCalled;

class RunSignupExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('runsignup', Provider::class);
    }
}
