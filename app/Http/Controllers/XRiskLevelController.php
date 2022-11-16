<?php

namespace App\Http\Controllers;

use App\Models\XRiskLevel;
use Illuminate\Http\Request;

class XRiskLevelController extends Controller
{
    public function index()
    {
        try {
            $list =  XRiskLevel::all();
            $response = responseFormat('success', $list);

        } catch (\Exception $exception) {
            $response = responseFormat('error', $exception->getMessage());
        }

        return response()->json($response);
    }

    public function store(Request $request)
    {
        try {

            $xRiskFactor = new XRiskLevel();
            $xRiskFactor->level_from = $request->level_from;
            $xRiskFactor->level_to = $request->level_to;
            $xRiskFactor->type = strtolower($request->type);
            $xRiskFactor->title_bn = strtolower($request->title_bn);
            $xRiskFactor->title_en = strtolower($request->title_en);
            $xRiskFactor->created_by = $request->created_by;
            $xRiskFactor->updated_by = $request->updated_by;
            $xRiskFactor->save();

            $response = responseFormat('success', 'Save Successfully');

        } catch (\Exception $exception) {
            $response = responseFormat('error', $exception->getMessage());
        }

        return response()->json($response);
    }

    public function update(Request $request, $id)
    {
        try {

            $xRiskFactor = XRiskLevel::find($id);
            $xRiskFactor->level_from = $request->level_from;
            $xRiskFactor->level_to = $request->level_to;
            $xRiskFactor->type = strtolower($request->type);
            $xRiskFactor->title_bn = strtolower($request->title_bn);
            $xRiskFactor->title_en = strtolower($request->title_en);
            $xRiskFactor->updated_by = $request->updated_by;
            $xRiskFactor->save();

            $response = responseFormat('success', 'Updated Successfully');

        } catch (\Exception $exception) {
            $response = responseFormat('error', $exception->getMessage());
        }

        return response()->json($response);
    }

    public function delete($id)
    {
        try {

            $xRiskFactor = XRiskLevel::find($id)->delete();

            $response = responseFormat('success', 'Deleted Successfully');


        } catch (\Exception $exception) {
            $response = responseFormat('error', $exception->getMessage());
        }

        return response()->json($response);
    }
}