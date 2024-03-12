<?php

namespace SocialiteProviders\Todoist;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TodoistExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('todoist', Provider::class);
    }
}
