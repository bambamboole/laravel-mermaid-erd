<?php declare(strict_types=1);
namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
