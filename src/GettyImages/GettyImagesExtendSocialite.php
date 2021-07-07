<?php

namespace SocialiteProviders\GettyImages;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GettyImagesExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('gettyimages', Provider::class);
    }
}
