<?php
declare(strict_types=1);

namespace sdo\Models;

class Race extends Model
{
    protected $table = 'races';
    
    /**
     * Lookups typically do not need timestamps.
     */
    public $timestamps = false;
    
    protected $guarded = [];
}