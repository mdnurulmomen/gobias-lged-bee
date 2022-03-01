<?php

namespace App\Services;

use App\Models\Apotti;
use App\Models\ApottiItem;
use App\Models\BroadSheetMovement;
use App\Models\BroadSheetReply;
use App\Models\BroadSheetReplyItem;
use App\Traits\ApiHeart;
use App\Traits\GenericData;
use Illuminate\Http\Request;
use DB;

class BroadsheetReplyService
{
    use GenericData, ApiHeart;

    public function getBroadSheetList(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {

            $broad_sheet_list = BroadSheetReply::withCount('broad_sheet_items')->paginate(config('bee_config.per_page_pagination'));

            return ['status' => 'success', 'data' => $broad_sheet_list];
        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }

    }

    public function getBroadSheetItem(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {

            $broad_sheet_list = BroadSheetReplyItem::with('apotti.fiscal_year')->where('broad_sheet_reply_id',$request->broad_sheet_id)->get();

            return ['status' => 'success', 'data' => $broad_sheet_list];
        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }

    }

    public function updateBroadSheetItem(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {

            $approval_status = $request->approval_status;

            $broad_sheet_list = BroadSheetReplyItem::where('broad_sheet_reply_id',$request->broad_sheet_id)
                                                    ->where('memo_id',$request->memo_id)->first();

            $broad_sheet_list->status = $request->apotti_status ?: $broad_sheet_list->status;
            $broad_sheet_list->jorito_ortho_poriman = $request->jorito_ortho ?: $broad_sheet_list->jorito_ortho_poriman;
            $broad_sheet_list->collected_amount = $request->collected_amount ?: $broad_sheet_list->collected_amount;
            $broad_sheet_list->adjusted_amount = $request->adjusted_amount ?: $broad_sheet_list->adjusted_amount;

            if($approval_status){
                $broad_sheet_list->approved_by = $request->approved_by;
                $broad_sheet_list->approver_bn  = $request->approver_bn;
                $broad_sheet_list->approver_en  = $request->approver_en;
                $broad_sheet_list->approver_designation_id  = $request->approver_designation_id;
                $broad_sheet_list->approver_designation_bn  = $request->approver_designation_bn;
                $broad_sheet_list->approver_designation_en  = $request->approver_designation_en;
            }

            $broad_sheet_list->save();

            return ['status' => 'success', 'data' => 'সিদ্ধান্ত দেয়া হয়েছে'];

        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }

    }

    public function getApottiItemInfo(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {
            $apottiIteminfo = ApottiItem::with(['fiscal_year'])->find($request->apotti_item_id);
            return ['status' => 'success', 'data' => $apottiIteminfo];
        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }

    }

    public function broadSheetMovement(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_id = $request->office_id ?: $cdesk->office_id;
        try {
            $office_db_con_response = $this->switchOffice($office_id);
            if (!isSuccessResponse($office_db_con_response)) {
                return ['status' => 'error', 'data' => $office_db_con_response];
            }

            //broad sheet movement data
            $broadSheetMovementData = [
                'broad_sheet_id' => $request->broad_sheet_id,
                'receiver_officer_id' => $request->receiver_officer_id,
                'receiver_office_id' => $request->receiver_office_id,
                'receiver_unit_id' => $request->receiver_unit_id,
                'receiver_unit_name_en' => $request->receiver_unit_name_en,
                'receiver_unit_name_bn' => $request->receiver_unit_name_bn,
                'receiver_employee_id' => $request->receiver_employee_id,
                'receiver_employee_name_en' => $request->receiver_employee_name_en,
                'receiver_employee_name_bn' => $request->receiver_employee_name_bn,
                'receiver_employee_designation_id' => $request->receiver_employee_designation_id,
                'receiver_employee_designation_en' => $request->receiver_employee_designation_en,
                'receiver_employee_designation_bn' => $request->receiver_employee_designation_bn,
                'receiver_officer_phone' => $request->receiver_officer_phone,
                'receiver_officer_email' => $request->receiver_officer_email,
                'sender_officer_id' => $cdesk->officer_id,
                'sender_office_id' => $cdesk->office_id,
                'sender_unit_id' => $cdesk->office_unit_id,
                'sender_unit_name_en' => $cdesk->office_unit_en,
                'sender_unit_name_bn' => $cdesk->office_unit_bn,
                'sender_employee_id' => $cdesk->officer_id,
                'sender_employee_name_en' => $cdesk->officer_en,
                'sender_employee_name_bn' => $cdesk->officer_bn,
                'sender_employee_designation_id' => $cdesk->designation_id,
                'sender_employee_designation_en' => $cdesk->designation_en,
                'sender_employee_designation_bn' => $cdesk->designation_bn,
                'sender_officer_phone' => $cdesk->phone,
                'sender_officer_email' => $cdesk->email,
                'comments' => $request->comments
            ];

            BroadSheetMovement::create($broadSheetMovementData);

            return ['status' => 'success', 'data' => ['সফলভাবে প্রেরণ করা হয়েছে']];

        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }
    }


    public function broadSheetLastMovement(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_id = $request->office_id ? $request->office_id : $cdesk->office_id;
        try {
            $office_db_con_response = $this->switchOffice($office_id);
            if (!isSuccessResponse($office_db_con_response)) {
                return ['status' => 'error', 'data' => $office_db_con_response];
            }

            $lastMovementInfo = BroadSheetMovement::where('broad_sheet_id', $request->broad_sheet_id)
                ->latest()
                ->first()
                ->toArray();

            return ['status' => 'success', 'data' => $lastMovementInfo];

        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }
    }

}
