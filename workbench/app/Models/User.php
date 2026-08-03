<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    /** @return HasMany<AiMessage, $this> */
    public function aiMessages(): HasMany
    {
        return $this->hasMany(AiMessage::class);
    }
}
