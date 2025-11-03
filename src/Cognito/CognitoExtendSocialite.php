<?php

namespace SocialiteProviders\Cognito;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CognitoExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('cognito', Provider::class);
    }
}
