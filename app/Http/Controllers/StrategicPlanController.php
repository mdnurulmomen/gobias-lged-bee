<?php

namespace App\Http\Controllers;

use App\Services\StrategicPlanService;
use Illuminate\Http\Request;

class StrategicPlanController extends Controller
{
    public function list(Request $request, StrategicPlanService $strategicPlanService)
    {
        $list = $strategicPlanService->list($request);

        if (isSuccessResponse($list)) {
            $response = responseFormat('success', $list['data']);
        } else {
            $response = responseFormat('error', $list['data']);
        }

        return response()->json($response);
    }

    public function store(Request $request, StrategicPlanService $strategicPlanService)
    {
        $store = $strategicPlanService->store($request);
        if (isSuccessResponse($store)) {
            $response = responseFormat('success', $store['data']);
        } else {
            $response = responseFormat('error', $store['data']);
        }

        return response()->json($response);
    }

    public function getIndividualStrategicPlan(Request $request, StrategicPlanService $strategicPlanService)
    {
        $store = $strategicPlanService->getIndividualStrategicPlan($request);
        if (isSuccessResponse($store)) {
            $response = responseFormat('success', $store['data']);
        } else {
            $response = responseFormat('error', $store['data']);
        }

        return response()->json($response);
    }

    public function getIndividualStrategicPlanYear(Request $request, StrategicPlanService $strategicPlanService)
    {
        $store = $strategicPlanService->getIndividualStrategicPlanYear($request);
        if (isSuccessResponse($store)) {
            $response = responseFormat('success', $store['data']);
        } else {
            $response = responseFormat('error', $store['data']);
        }

        return response()->json($response);
    }
}
