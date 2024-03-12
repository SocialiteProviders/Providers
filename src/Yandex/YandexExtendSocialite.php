<?php

namespace SocialiteProviders\Yandex;

use SocialiteProviders\Manager\SocialiteWasCalled;

class YandexExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('yandex', Provider::class);
    }
}
