<?php

namespace SocialiteProviders\MusicBrainz;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MusicBrainzExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('musicbrainz', Provider::class);
    }
}
