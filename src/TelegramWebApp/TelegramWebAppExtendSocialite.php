<?php

namespace SocialiteProviders\TelegramWebApp;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TelegramWebAppExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('telegramwebapp', Provider::class);
    }
}
