<?php

namespace SocialiteProviders\GettyImages;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GettyImagesExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('gettyimages', Provider::class);
    }
}
