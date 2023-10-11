<?php

namespace SocialiteProviders\Imgur;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ImgurExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('imgur', Provider::class);
    }
}
