<?php

namespace SocialiteProviders\ClickUp;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ClickUpExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('clickup', Provider::class);
    }
}
