<?php

namespace SocialiteProviders\Threads;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ThreadsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('threads', Provider::class);
    }
}
