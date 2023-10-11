<?php

namespace SocialiteProviders\Pushbullet;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PushbulletExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('pushbullet', Provider::class);
    }
}
