<?php

namespace SocialiteProviders\Mattermost;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MattermostExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('mattermost', Provider::class);
    }
}
