<?php

namespace App\Services;
use App\Models\AnnualPlanEntitie;
use App\Models\Apotti;
use App\Models\ApottiItem;
use App\Models\ApottiRAirMap;
use App\Models\RAir;
use App\Models\XDefaultSetting;
use App\Models\XFiscalYear;
use App\Traits\ApiHeart;
use App\Traits\GenericData;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class RpuAirReportService
{
    use GenericData, ApiHeart;

    public function airSendToRpu(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {
            $air_info = RAir::where('id',$request->air_id)->first();
            $fiscal_year = XFiscalYear::find($air_info->fiscal_year_id);

            //get apotti map data
            $apotti_map_list = ApottiRAirMap::where('rairs_id',$request->air_id)
                ->where('is_delete',0)->pluck('apotti_id');

            //get apotti list
            $apotti_list = Apotti::with(['apotti_items'])->whereIn('id',$apotti_map_list)
                ->where(function($query){
                    $query->where('apotti_type','sfi')
                        ->orWhere('apotti_type','non-sfi');
                })->get();

            //get entity list
            $entity_list = AnnualPlanEntitie::where('annual_plan_id',$air_info->annual_plan_id)->get();

            $air_list = [];
            foreach ($entity_list as $entity){
                $data['report_number'] = $air_info->report_number;
                $data['report_name'] = $air_info->report_name;
                $data['air_id'] = $air_info->id;
                $data['fiscal_year_id'] = $air_info->fiscal_year_id;
                $data['cost_center_id'] = $entity->entity_id;
                $data['fiscal_year'] = $fiscal_year->start.'-'.$fiscal_year->end;
                $data['annual_plan_id'] = $air_info->annual_plan_id;
                $data['audit_plan_id'] = $air_info->audit_plan_id;
                $data['activity_id'] = $air_info->activity_id;
                $data['air_description'] = gzuncompress(getDecryptedData($air_info->air_description));
                $data['directorate_id'] = $cdesk->office_id;
                $data['directorate_en'] = $cdesk->office_name_en;
                $data['directorate_bn'] = $cdesk->office_name_bn;
                $data['sender_id'] = $cdesk->officer_id;
                $data['sender_en'] = $cdesk->officer_en;
                $data['sender_bn'] = $cdesk->officer_bn;
                $data['send_date'] = date('Y-m-d');

                $air_list[] = $data;

            }

            //send data
            if (!empty($air_list)){
                $send_air_data['air_list'] = $air_list;
                $send_air_data['apotti_list'] = $apotti_list;
                $send_air_data['air_id'] = $request->air_id;
                $send_air_data['directorate_id'] = $cdesk->office_id;
                $send_air_data['directorate_en'] = $cdesk->office_name_en;
                $send_air_data['directorate_bn'] = $cdesk->office_name_bn;

                $send_air_to_rpu = $this->initRPUHttp()->post(config('cag_rpu_api.send_air_to_rpu'), $send_air_data)->json();

                if ($send_air_to_rpu['status'] == 'success') {
                    $air_info->is_sent = 1;
                    $air_info->save();

                    $tagidPotroSendingDays = XDefaultSetting::select('setting_value')
                        ->where('setting_key','tagid_potro_sending_days')
                        ->where('is_active',1)
                        ->first()
                        ->toArray();

                    //tagid potro
                    $tagid_potro_task_data = [
                        'task_title_en' => $air_info->report_name.' এ তাগিদ পত্র প্রেরণ করুন',
                        'task_title_bn' => $air_info->report_name.' এ তাগিদ পত্র প্রেরণ করুন',
                        'description' => '',
                        'meta_data' => base64_encode(json_encode(['r_air_id' => $air_info->id, 'return_url' => ''])),
                        'task_start_end_date_time' => Carbon::now()->addDays($tagidPotroSendingDays['setting_value'])->format('d/m/Y H:i A') . ' - ' . Carbon::now()->addDays($tagidPotroSendingDays['setting_value'])->format('d/m/Y H:i A'),
                        'notifications' => json_encode([[
                            "medium" => "email",
                            "interval" => "1",
                            "unit" => "days",
                        ]]),
                    ];
                    (new AmmsPonjikaServices())->createTask($tagid_potro_task_data, $cdesk);

                    $doLetterSendingDays = XDefaultSetting::select('setting_value')
                        ->where('setting_key','do_letter_sending_days')
                        ->where('is_active',1)
                        ->first()
                        ->toArray();

                    //do letter
                    $do_letter_task_data = [
                        'task_title_en' => $air_info->report_name.' এ ডিও লেটার প্রেরণ করুন',
                        'task_title_bn' => $air_info->report_name.' এ ডিও লেটার প্রেরণ করুন',
                        'description' => '',
                        'meta_data' => base64_encode(json_encode(['r_air_id' => $air_info->id, 'return_url' => ''])),
                        'task_start_end_date_time' => Carbon::now()->addDays($doLetterSendingDays['setting_value'])->format('d/m/Y H:i A') . ' - ' . Carbon::now()->addDays($doLetterSendingDays['setting_value'])->format('d/m/Y H:i A'),
                        'notifications' => json_encode([[
                            "medium" => "email",
                            "interval" => "1",
                            "unit" => "days",
                        ]]),
                    ];
                    (new AmmsPonjikaServices())->createTask($do_letter_task_data, $cdesk);

                    return ['status' => 'success', 'data' => 'Air Send Successfully'];
                }
                return ['status' => 'error', 'data' => $send_air_to_rpu];
            }else{
                return ['status' => 'error', 'data' => 'Entity not found'];
            }
        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }

    }

    public function receivedAirByRpu(Request $request): array
    {
        $office_db_con_response = $this->switchOffice($request->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {
            $air_info = RAir::find($request->air_id);
            $air_info->is_received = 1;
            $air_info->save();
            return ['status' => 'success', 'data' => 'Air Received Successfully'];
        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }
    }

    public function apottiItemResponseByRpu(Request $request): array
    {
        $office_db_con_response = $this->switchOffice($request->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {
            $apottiItem = ApottiItem::find($request->apotti_item_id);
            $apottiItem->unit_response = $request->unit_response;
            $apottiItem->entity_response = $request->entity_response;
            $apottiItem->ministry_response = $request->ministry_response;
            $apottiItem->save();
            return ['status' => 'success', 'data' => 'Response Received Successfully'];
        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }
    }
}
