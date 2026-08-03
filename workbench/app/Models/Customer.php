<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /** @return Attribute<string, never> */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): string => ucfirst($value),
        );
    }
}
