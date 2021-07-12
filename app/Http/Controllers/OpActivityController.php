<?php

namespace App\Http\Controllers;

use App\Http\Requests\OpActivity\SaveRequest;
use App\Http\Requests\OpActivity\SearchActivities;
use App\Http\Requests\OpActivity\ShowOrDeleteRequest;
use App\Http\Requests\OpActivity\UpdateRequest;
use App\Models\OpActivity;
use App\Models\XStrategicPlanOutput;
use Illuminate\Http\Request;

class OpActivityController extends Controller
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        if ($request->per_page && $request->page && !$request->all) {
            $opActivities = OpActivity::with('plan_output.plan_outcome.plan_duration')->paginate($request->per_page);
        } else {
            $opActivities = OpActivity::with('plan_output.plan_outcome.plan_duration')->get();
        }

        if ($opActivities) {
            $response = responseFormat('success', $opActivities);
        } else {
            $response = responseFormat('error', 'Operational Plan Activity Not Found');
        }
        return response()->json($response, 200);
    }

    public function findActivities(SearchActivities $request): \Illuminate\Http\JsonResponse
    {

        $output_id = $request->output_id;
        $outcome_id = $request->outcome_id;
        $fiscal_year_id = $request->fiscal_year_id;

//        $query = OpActivity::query();
        $query = XStrategicPlanOutput::query();

        $query->when($output_id, function ($q, $output_id) {
            return $q->where('id', $output_id);
        });
        $query->when($outcome_id, function ($q, $outcome_id) {
            return $q->where('outcome_id', $outcome_id);
        });
        $query->when($fiscal_year_id, function ($q, $fiscal_year_id) {
            return $q->where('fiscal_year_id', $fiscal_year_id);
        });

        $activities = $query->with('activities.children')->get();

//        $activities = OpActivity::where('output_id', $request->output_id)->with('children')->get();

        if (!empty($activities)) {
            $response = responseFormat('success', $activities);
        } else {
            $response = responseFormat('error', 'Not Found');
        }

        return response()->json($response);
    }

    public function store(SaveRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validated = $request->validated();
            if ($validated['activity_parent_id'] && $validated['activity_parent_id'] > 0) {
                $validated['is_parent'] = 1;
            }
            $validated['duration_id'] = $this->durationIdFromFiscalYear($validated['fiscal_year_id']);
            OpActivity::create($validated);
            $response = responseFormat('success', 'Operational Plan Activity Created Successfully');
        } catch (\Exception $exception) {
            $response = responseFormat('error', $exception->getMessage(), ['code' => $exception->getCode()]);
        }

        return response()->json($response);
    }

    public function show(ShowOrDeleteRequest $request): \Illuminate\Http\JsonResponse
    {
        $opActivity = OpActivity::with('plan_output.plan_outcome.plan_duration')->where('id', $request->activity_id)
            ->first();

        if (!empty($opActivity)) {
            $response = responseFormat('success', $opActivity);
        } else {
            $response = responseFormat('error', 'Not Found');
        }

        return response()->json($response);
    }

    public function update(UpdateRequest $request): \Illuminate\Http\JsonResponse
    {
        $opActivity = OpActivity::find($request->activity_id);
        try {
            $validated = $request->validated();
            if ($validated['activity_parent_id']) {
                $validated['is_parent'] = $validated['activity_parent_id'] > 0 ? 1 : 0;
            }
            $opActivity->update($validated);
            $response = responseFormat('success', 'Successfully Updated');
        } catch (\Exception $exception) {
            $response = responseFormat('error', $exception->getMessage());
        }

        return response()->json($response);
    }

    public function destroy(ShowOrDeleteRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            OpActivity::find($request->activity_id)->delete();
            $response = responseFormat('success', 'Successfully Deleted');
        } catch (\Exception $exception) {
            $response = responseFormat('error', $exception->getMessage());
        }
        return response()->json($response);
    }
}
