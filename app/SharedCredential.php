<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SharedCredential extends Model
{
    protected $guarded = [];

    public static function deleteExpired(): void
    {
        static::where('expire_at', '<', DB::raw('NOW()'))->delete();
    }
}
