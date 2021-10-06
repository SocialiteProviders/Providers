<?php

namespace SocialiteProviders\Cognito;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CognitoExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('cognito', Provider::class);
    }
}
