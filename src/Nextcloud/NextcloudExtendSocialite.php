<?php

namespace SocialiteProviders\Nextcloud;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NextcloudExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('nextcloud', Provider::class);
    }
}
