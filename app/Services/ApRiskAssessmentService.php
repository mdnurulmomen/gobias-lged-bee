<?php

namespace App\Services;

use App\Http\Controllers\XRiskAssessmentController;
use App\Models\ApRiskAssessment;
use App\Models\ApRiskAssessmentItem;
use App\Models\XRiskAssessment;
use App\Traits\ApiHeart;
use App\Traits\GenericData;
use Illuminate\Http\Request;

class ApRiskAssessmentService
{
    use GenericData, ApiHeart;

    public function store(Request $request)
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        \DB::beginTransaction();
        try {
            $risk_assessment = new ApRiskAssessment;
            $risk_assessment->fiscal_year_id = $request->fiscal_year_id;
            $risk_assessment->activity_id = $request->activity_id;
            $risk_assessment->audit_plan_id = $request->audit_plan_id;
            $risk_assessment->risk_assessment_type = $request->risk_assessment_type;
            $risk_assessment->total_risk_value = $request->total_number;
            $risk_assessment->risk_rate = $request->risk_rate;
            $risk_assessment->risk = $request->risk;
            $risk_assessment->created_by = $cdesk->officer_id;
            $risk_assessment->created_by_name_en = $cdesk->officer_en;
            $risk_assessment->created_by_name_bn = $cdesk->officer_bn;
            $risk_assessment->save();

            foreach ($request->risk_assessments as $item) {
                $risk_assessment_item = new ApRiskAssessmentItem;
                $risk_assessment_item->ap_risk_assessment_id = $risk_assessment->id;
                $risk_assessment_item->x_risk_assessment_id = $item['risk_assessment_id'];
                $risk_assessment_item->risk_assessment_title_en = $item['risk_assessment_title_en'];
                $risk_assessment_item->risk_assessment_title_bn = $item['risk_assessment_title_bn'];
                $risk_assessment_item->yes = $item['yes'];
                $risk_assessment_item->no = $item['no'];
                $risk_assessment_item->risk_value = $item['risk_value'];
                $risk_assessment_item->save();
            }
            \DB::commit();
            return ['status' => 'success', 'data' => 'save data successful'];
        } catch (\Exception $exception) {
            \DB::rollback();
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }
    }

    public function update(Request $request)
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        \DB::beginTransaction();
        try {
            $risk_assessment = ApRiskAssessment::find($request->id);
            $risk_assessment->fiscal_year_id = $request->fiscal_year_id;
            $risk_assessment->activity_id = $request->activity_id;
            $risk_assessment->audit_plan_id = $request->audit_plan_id;
            $risk_assessment->risk_assessment_type = $request->risk_assessment_type;
            $risk_assessment->total_risk_value = $request->total_number;
            $risk_assessment->risk_rate = $request->risk_rate;
            $risk_assessment->risk = $request->risk;
            $risk_assessment->updated_by = $cdesk->officer_id;
            $risk_assessment->updated_by_name_en = $cdesk->officer_en;
            $risk_assessment->updated_by_name_bn = $cdesk->officer_bn;
            $risk_assessment->save();

            ApRiskAssessmentItem::where('ap_risk_assessment_id',$request->id)->delete();

            foreach ($request->risk_assessments as $item) {
                $risk_assessment_item = new ApRiskAssessmentItem;
                $risk_assessment_item->ap_risk_assessment_id = $risk_assessment->id;
                $risk_assessment_item->x_risk_assessment_id = $item['risk_assessment_id'];
                $risk_assessment_item->risk_assessment_title_en = $item['risk_assessment_title_en'];
                $risk_assessment_item->risk_assessment_title_bn = $item['risk_assessment_title_bn'];
                $risk_assessment_item->yes = $item['yes'];
                $risk_assessment_item->no = $item['no'];
                $risk_assessment_item->risk_value = $item['risk_value'];
                $risk_assessment_item->save();
            }
            \DB::commit();
            return ['status' => 'success', 'data' => 'update data successful'];
        } catch (\Exception $exception) {
            \DB::rollback();
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }
    }

    public function apRiskAssessmentList(Request $request): array
    {

        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {

            $ap_risk_assessment_list = ApRiskAssessment::select('id','total_risk_value','risk_rate','risk')->where('risk_assessment_type',$request->risk_assessment_type)
                ->where('fiscal_year_id',$request->fiscal_year_id)
                ->where('audit_plan_id',$request->audit_plan_id)
                ->first();

            if ($ap_risk_assessment_list) {

                $ap_risk_assessment_item_list = ApRiskAssessmentItem::where('ap_risk_assessment_id', $ap_risk_assessment_list->id)->get();

                $item = [];
                foreach ($ap_risk_assessment_item_list as $item) {
                    $item_arry[$item->x_risk_assessment_id] = array(
                        'id' => $item->x_risk_assessment_id,
                        'yes' => $item->yes,
                        'no' => $item->no,
                        'risk_value' => $item->risk_value,
                    );
                    $item = $item_arry;
                }

                $ap_risk_assessment_list = array(
                    'id' => $ap_risk_assessment_list->id,
                    'total_risk_value' => $ap_risk_assessment_list->total_risk_value,
                    'risk_rate' => $ap_risk_assessment_list->risk_rate,
                    'risk' => $ap_risk_assessment_list->risk,
                    'risk_assessment_items' => $item,
                );

            }


            return ['status' => 'success', 'data' => $ap_risk_assessment_list];

        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];

        }

    }

}
