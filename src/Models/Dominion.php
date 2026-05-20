<?php
declare(strict_types=1);
namespace sdo\Models;

class Dominion extends Model {
    protected $table = 'dominions';

    public function race() {
        return $this->belongsTo(Race::class, 'race_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}