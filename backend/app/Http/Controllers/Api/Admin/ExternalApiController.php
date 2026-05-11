<?php
# 관리자페이지 외부 API 관련 컨트롤러

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExternalApiController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 20);
        $externalApis = DB::table('stocks')->paginate($perPage);

        return response()->json([
            'data' => [
                'data' => $externalApis->items(),
                'pagination' => [
                    'current_page' => $externalApis->currentPage(),
                    'last_page' => $externalApis->lastPage(),
                    'per_page' => $externalApis->perPage(),
                    'total' => $externalApis->total(),
                    'from' => $externalApis->firstItem(),
                    'to' => $externalApis->lastItem(),
                    'has_more_pages' => $externalApis->hasMorePages(),
                ],
            ],
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
