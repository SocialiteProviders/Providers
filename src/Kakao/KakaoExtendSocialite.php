<?php

namespace SocialiteProviders\Kakao;

use SocialiteProviders\Manager\SocialiteWasCalled;

class KakaoExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('kakao', KakaoProvider::class);
    }
}
