<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $dbname = DB::connection()->getDatabaseName();
            return response()->json([
                'status' => 'Database connection OK',
                'message' => 'Database name: ' . $dbname
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'status' => 'Database connection lost',
                'message' => $ex->getMessage()
            ])->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
