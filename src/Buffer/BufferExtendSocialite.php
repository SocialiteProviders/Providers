<?php

namespace SocialiteProviders\Buffer;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BufferExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('buffer', Provider::class);
    }
}
