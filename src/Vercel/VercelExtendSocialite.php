<?php

namespace SocialiteProviders\Vercel;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VercelExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('vercel', Provider::class);
    }
}
