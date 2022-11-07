<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function index()
    {
        try {
            $dbname = DB::connection()->getDatabaseName();
            return response([
                'status' => 'Database connection OK',
                'message' => 'Database name: ' . $dbname
            ]);
        } catch (\Exception $ex) {
            return response([
                'status' => 'Database connection lost',
                'message' => $ex->getMessage()
            ])->setStatusCode(500);
        }
    }
}
