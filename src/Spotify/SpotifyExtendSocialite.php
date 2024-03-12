<?php

namespace SocialiteProviders\Spotify;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SpotifyExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('spotify', Provider::class);
    }
}
