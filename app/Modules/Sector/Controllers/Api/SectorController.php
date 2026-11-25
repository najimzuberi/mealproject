<?php

namespace App\Modules\Sector\Controllers\Api;

use App\Modules\Sector\Models\Sector;
use App\Modules\Sector\Resources\SectorResource;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SectorController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(SectorResource::collection(
            Sector::query()->where('status', 'active')->orderBy('name')->paginate(),
        ));
    }
}
