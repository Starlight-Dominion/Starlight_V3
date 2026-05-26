<?php

declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;

abstract class Model extends BaseModel
{
    protected $guarded = [];
    protected $hidden = [];
}
