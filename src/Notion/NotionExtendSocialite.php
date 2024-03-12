<?php

namespace SocialiteProviders\Notion;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NotionExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('notion', Provider::class);
    }
}
