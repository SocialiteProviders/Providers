<?php

namespace SocialiteProviders\Telegram;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TelegramExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('telegram', Provider::class);
    }
}
