<?php

namespace SocialiteProviders\Vimeo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VimeoExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('vimeo', Provider::class);
    }
}
