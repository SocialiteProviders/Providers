<?php

namespace SocialiteProviders\QuickBooks;

use SocialiteProviders\Manager\SocialiteWasCalled;

class QuickBooksExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('quickbooks', Provider::class);
    }
}
