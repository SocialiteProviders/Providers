<?php

namespace SocialiteProviders\MediaCube;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MediaCubeExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('mediacube', Provider::class);
    }
}
