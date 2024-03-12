<?php

namespace SocialiteProviders\MediaCube;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MediaCubeExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('mediacube', Provider::class);
    }
}
