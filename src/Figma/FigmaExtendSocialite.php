<?php

namespace SocialiteProviders\Figma;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FigmaExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('Figma', Provider::class);
    }
}
