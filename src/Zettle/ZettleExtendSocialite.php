<?php

namespace SocialiteProviders\Zettle;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ZettleExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('zettle', ZettleProvider::class);
    }
}
