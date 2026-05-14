<?php

namespace App\Modules\Billing\Models;

use App\Modules\Branches\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceSequence extends Model
{
    protected $fillable = ['branch_id', 'year', 'next_number'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
