<?php

namespace SocialiteProviders\Telegram;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TelegramExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('telegram', Provider::class);
    }
}
