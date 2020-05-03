<?php

namespace SocialiteProviders\GettyImages;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GettyImagesExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'gettyimages', __NAMESPACE__.'\Provider'
        );
    }
}
