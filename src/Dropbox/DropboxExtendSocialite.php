<?php

namespace SocialiteProviders\Dropbox;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DropboxExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('dropbox', Provider::class);
    }
}
