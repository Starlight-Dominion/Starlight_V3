<?php
declare(strict_types=1);

namespace sdo\Models;

class GameSetting extends Model
{
    protected $table = 'game_settings';

    protected $primaryKey = 'setting_key';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;
}
