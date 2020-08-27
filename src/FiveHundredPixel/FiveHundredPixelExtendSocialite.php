<?php

namespace SocialiteProviders\FiveHundredPixel;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FiveHundredPixelExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('500px', Provider::class, Server::class);
    }
}
