<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SharedCredential extends Model
{
    protected $guarded = [];

    public static function deleteExpired(): void
    {
        static::where('expire_at', '<', Carbon::now())->delete();
    }
}
