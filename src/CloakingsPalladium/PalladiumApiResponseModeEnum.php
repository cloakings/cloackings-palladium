<?php

namespace Cloakings\CloakingsPalladium;

enum PalladiumApiResponseModeEnum: int
{
    case Unknown = 0;
    case Iframe = 1;
    case Redirect = 2;
    case TargetPath = 3;
    case Content = 4;
    case EmptyIfEmptyStatus = 5;
    case Empty = 6;
}
