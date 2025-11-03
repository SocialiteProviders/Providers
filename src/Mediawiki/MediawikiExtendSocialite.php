<?php

namespace SocialiteProviders\Mediawiki;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MediawikiExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('mediawiki', Provider::class);
    }
}
