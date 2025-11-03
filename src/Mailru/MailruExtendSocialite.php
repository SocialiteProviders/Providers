<?php

namespace SocialiteProviders\Mailru;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MailruExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('mailru', Provider::class);
    }
}
