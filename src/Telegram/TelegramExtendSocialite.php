<?php

namespace SocialiteProviders\Telegram;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TelegramExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('telegram', __NAMESPACE__.'\Provider');
    }
}
