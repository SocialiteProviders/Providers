<?php

namespace SocialiteProviders\WordPress;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WordPressExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('wordpress', Provider::class);
    }
}
