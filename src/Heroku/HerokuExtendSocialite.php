<?php

namespace SocialiteProviders\Heroku;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HerokuExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'heroku',
            __NAMESPACE__.'\Provider'
        );
    }
}
