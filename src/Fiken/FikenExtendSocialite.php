<?php

namespace SocialiteProviders\Fiken;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FikenExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('fiken', FikenProvider::class);
    }
}
