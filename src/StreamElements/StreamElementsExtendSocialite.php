<?php

namespace SocialiteProviders\StreamElements;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StreamElementsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('streamelements', Provider::class);
    }
}
