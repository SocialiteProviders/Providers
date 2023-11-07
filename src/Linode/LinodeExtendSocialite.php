<?php

namespace SocialiteProviders\Linode;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LinodeExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('linode', Provider::class);
    }
}
