<?php
declare(strict_types=1);

namespace sdo\Models;

class Race extends Model
{
    protected $table = 'races';
    
    // Disable timestamps as our migration didn't add created_at/updated_at to this lookup table
    public $timestamps = false;
    
    // Allow mass assignment for the auto-seeder fallback
    protected $guarded = [];
}