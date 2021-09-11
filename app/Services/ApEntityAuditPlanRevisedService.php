<?php

namespace App\Services;

use App\Models\AnnualPlan;
use App\Models\ApEntityAuditPlan;
use App\Models\ApEntityIndividualAuditPlan;
use App\Models\ApOrganizationYearlyPlanResponsibleParty;
use App\Models\AuditTemplate;
use App\Models\OpActivity;
use App\Traits\GenericData;
use Illuminate\Http\Request;

class ApEntityAuditPlanRevisedService
{
    use GenericData;

    public function allEntityAuditPlanLists(Request $request): array
    {
        $fiscal_year_id = $request->fiscal_year_id;
        $cdesk = json_decode($request->cdesk, false);

        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {
            if ($request->per_page && $request->page && !$request->all) {
                $all_entities = AnnualPlan::with('audit_plans')->where('fiscal_year_id', $fiscal_year_id)->paginate($request->per_page);
            } else {
                $all_entities = AnnualPlan::with('audit_plans')->where('fiscal_year_id', $fiscal_year_id)->get();
            }
            return ['status' => 'success', 'data' => $all_entities];
        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }
    }

    public function createNewAuditPlan(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        try {
            $office_db_con_response = $this->switchOffice($cdesk->office_id);
            if (!isSuccessResponse($office_db_con_response)) {
                return ['status' => 'error', 'data' => $office_db_con_response];
            }

            $annual_plan = AnnualPlan::where('id', $request->annual_plan_id)->with('fiscal_year')->first()->toArray();
            $activity = OpActivity::where('id', $request->activity_id)->first()->toArray();
            $audit_template = AuditTemplate::where('template_type', $activity['activity_type'])->where('lang', 'bn')->first()->toArray();

            $data['annual_plan'] = $annual_plan;
            $data['plan_description'] = $audit_template['content'];
            return ['status' => 'success', 'data' => $data];
        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }
    }


    public function editAuditPlan(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        try {
            $office_db_con_response = $this->switchOffice($cdesk->office_id);
            if (!isSuccessResponse($office_db_con_response)) {
                return ['status' => 'error', 'data' => $office_db_con_response];
            }

            $annual_plan = AnnualPlan::where('id', $request->annual_plan_id)->with('fiscal_year')->first()->toArray();
            $activity = OpActivity::where('id', $request->activity_id)->first()->toArray();
            $audit_template = AuditTemplate::where('template_type', $activity['activity_type'])->first()->toArray();

            $data['annual_plan'] = $annual_plan;
            $data['plan_description'] = $audit_template['content'];
            return ['status' => 'success', 'data' => $data];
        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }
    }

    public function update(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);

        $this->switchOffice($cdesk->office_id);

        try {
            $annual_plan_data = AnnualPlan::where('id', $request->annual_plan_id)->select('schedule_id', 'milestone_id', 'fiscal_year_id')->first();
            $draft_plan_data = [
                'activity_id' => $request->activity_id,
                'schedule_id' => $annual_plan_data->schedule_id,
                'milestone_id' => $annual_plan_data->milestone_id,
                'fiscal_year_id' => $annual_plan_data->fiscal_year_id,
                'annual_plan_id' => $request->annual_plan_id,
                'plan_description' => $request->plan_description,
                'draft_office_id' => $cdesk->office_id,
                'draft_unit_id' => $cdesk->office_unit_id,
                'draft_unit_name_en' => $cdesk->office_unit_en,
                'draft_unit_name_bn' => $cdesk->office_unit_bn,
                'draft_designation_id' => $cdesk->designation_id,
                'draft_designation_name_en' => $cdesk->designation_en,
                'draft_designation_name_bn' => $cdesk->designation_bn,
                'draft_officer_id' => $cdesk->officer_id,
                'draft_officer_name_en' => $cdesk->officer_en,
                'draft_officer_name_bn' => $cdesk->officer_bn,
                'created_by' => $cdesk->officer_id,
                'modified_by' => $cdesk->officer_id,
                'device_type' => '',
                'device_id' => '',
            ];


            if ($request->has('audit_plan_id') && $request->audit_plan_id > 0) {
                $draft_plan = ApEntityIndividualAuditPlan::where('id', $request->audit_plan_id)
                    ->where('activity_id', $request->activity_id)
                    ->where('annual_plan_id', $request->annual_plan_id)
                    ->update($draft_plan_data);
            } else {
                $draft_plan = ApEntityIndividualAuditPlan::create($draft_plan_data);
            }
            $data = ['status' => 'success', 'data' => $draft_plan];
        } catch (\Exception $e) {
            $data = ['status' => 'error', 'data' => $e->getMessage()];
        }
        $this->emptyOfficeDBConnection();

        return $data;
    }

    public function showEntityAuditPlan(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $this->switchOffice($cdesk->office_id);

        try {
            $plan = ApEntityAuditPlan::where('party_id', $request->party_id)->where('ap_organization_yearly_plan_rp_id', $request->yearly_plan_rp_id)->first();
            if ($plan) {
                $content = $plan['plan_description'];
                unset($plan['plan_description']);
                $plan = ['content' => $content, 'plan_details' => $plan, 'is_draft' => true];
                $data = ['status' => 'success', 'data' => $plan];
            } else {
                $yearly_plan_rp = ApOrganizationYearlyPlanResponsibleParty::where('id', $request->yearly_plan_rp_id)->with('activity')->first();
                if (!$yearly_plan_rp) {
                    throw new \Exception('Yearly Plan Of RP Unit Not Found!');
                }
                $activity_type = $yearly_plan_rp->activity->activity_type;
                if ($activity_type) {
                    $template = AuditTemplate::select('content')->where('lang', $request->lang)->where('template_type', $activity_type)->where('status', 1)->first()->toArray();
                    $template['is_draft'] = false;
                    $template['plan_details'] = new \stdClass();
                    $data = ['status' => 'success', 'data' => $template];
                } else {
                    throw new \Exception('No template found');
                }
            }
        } catch (\Exception $e) {
            $data = ['status' => 'error', 'data' => $e->getMessage()];
        }
        $this->emptyOfficeDBConnection();
        return $data;

    }

}