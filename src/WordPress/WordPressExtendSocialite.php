<?php

namespace SocialiteProviders\WordPress;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WordPressExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('wordpress', __NAMESPACE__.'\Provider');
    }
}
