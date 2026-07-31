<?php declare(strict_types=1);
namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Workbench\App\Enums\OrderStatus;

class Order extends Model
{
    use SoftDeletes;

    #[\Override]
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'placed_at' => 'immutable_datetime',
        ];
    }
}
