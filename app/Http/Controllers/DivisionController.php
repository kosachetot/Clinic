<?php

namespace App\Http\Controllers;

use App\Support\Hris;
use Illuminate\Http\JsonResponse;

class DivisionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Hris::divisions()->values());
    }
}
