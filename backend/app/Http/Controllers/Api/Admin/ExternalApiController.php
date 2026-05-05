<?php
# 관리자페이지 외부 API 관련 컨트롤러

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExternalApiController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => DB::table('stocks')->limit(20)->get(),
        ]);
    }

    public function run($id)
    {
        return response()->json([
            'success' => true,
            'message' => 'API 실행 완료',
            'data' => [
                'id' => $id,
                'last_status' => 'success',
                'last_run_at' => now()->toDateTimeString(),
            ],
        ]);
    }
}
