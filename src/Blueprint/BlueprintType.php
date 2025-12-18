<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Blueprint;

enum BlueprintType: string
{
    case Site = 'site';
    case Page = 'page';
    case File = 'file';
    case User = 'user';
    case Field = 'field';
    case Section = 'section';
    case Block = 'block';
    case Unknown = 'unknown';
}
