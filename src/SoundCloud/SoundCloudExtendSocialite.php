<?php

namespace SocialiteProviders\SoundCloud;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SoundCloudExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('soundcloud', Provider::class);
    }
}
