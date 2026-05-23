<?php
declare(strict_types=1);

namespace sdo\Models;

class BankTransaction extends Model
{
    protected $table = 'bank_transactions';

    public $timestamps = false;

    protected $casts = [
        'kingdom_id' => 'integer',
        'amount' => 'integer',
        'created_at' => 'datetime'
    ];

    public function dominion()
    {
        return $this->belongsTo(Dominion::class, 'kingdom_id');
    }
}
