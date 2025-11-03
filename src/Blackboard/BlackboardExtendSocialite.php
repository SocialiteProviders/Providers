<?php

namespace SocialiteProviders\Blackboard;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BlackboardExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('blackboard', Provider::class);
    }
}
