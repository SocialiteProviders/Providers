<?php

namespace SocialiteProviders\YouTube;

use SocialiteProviders\Manager\SocialiteWasCalled;

class YouTubeExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('youtube', Provider::class);
    }
}
