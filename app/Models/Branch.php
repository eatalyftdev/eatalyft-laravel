<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Branch extends Model
{
    protected $guarded = ['id'];
    public function agency(): BelongsTo { return $this->belongsTo(Agency::class); }
    public function city(): BelongsTo { return $this->belongsTo(City::class); }
}
