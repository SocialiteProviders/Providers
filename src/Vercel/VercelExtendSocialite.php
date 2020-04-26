<?php

namespace SocialiteProviders\Vercel;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VercelExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('vercel', __NAMESPACE__.'\Provider');
    }
}
