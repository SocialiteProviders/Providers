<?php

namespace SocialiteProviders\Zendesk;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ZendeskExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('zendesk', Provider::class);
    }
}
