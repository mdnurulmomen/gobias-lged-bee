<?php

namespace App\Services;

use App\Models\AuditAssessmentScore;
use App\Models\AuditAssessmentScoreItem;
use App\Traits\ApiHeart;
use App\Traits\GenericData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditAssessmentScoreService
{
    use GenericData, ApiHeart;

    public function store(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }

        \DB::beginTransaction();

        try {
            $auditAssessmentScore = new AuditAssessmentScore();
            $auditAssessmentScore->category_id = $request->category_id;
            $auditAssessmentScore->category_title_en = $request->category_title_en;
            $auditAssessmentScore->category_title_bn = $request->category_title_bn;
            $auditAssessmentScore->fiscal_year_id = $request->fiscal_year_id;
            $auditAssessmentScore->ministry_id = $request->ministry_id;
            $auditAssessmentScore->ministry_name_en = $request->ministry_name_en;
            $auditAssessmentScore->ministry_name_bn = $request->ministry_name_bn;
            $auditAssessmentScore->entity_id = $request->entity_id;
            $auditAssessmentScore->entity_name_bn = $request->entity_name_bn;
            $auditAssessmentScore->entity_name_en = $request->entity_name_en;
            $auditAssessmentScore->point = $request->point;
            $auditAssessmentScore->created_by = $cdesk->officer_id;
            $auditAssessmentScore->updated_by = $cdesk->officer_id;
            $auditAssessmentScore->save();

            //for items
            $finalItems = [];
            foreach ($request->criteria_ids as $key => $criteria_id){
                if (!empty($request->scores[$key])){
                    array_push($finalItems, array(
                            'audit_assessment_score_id' => $auditAssessmentScore->id,
                            'criteria_id' => $criteria_id,
                            'value' =>  $request->values[$key],
                            'score' =>  $request->scores[$key]
                        )
                    );
                }
            }

            if (!empty($finalItems)){
                AuditAssessmentScoreItem::insert($finalItems);
            }

            \DB::commit();
            return ['status' => 'success', 'data' => 'Saved Successfully'];
        } catch (\Exception $exception) {
            \DB::rollback();
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }

    }

    public function list(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {
            $responseData = AuditAssessmentScore::with(['fiscal_year'])
                ->addSelect(['total_score' => function ($query) {
                    $query->select(\DB::raw('sum(score)'))
                        ->from('audit_assessment_score_items')
                        ->whereColumn('audit_assessment_score_id', 'audit_assessment_scores.id')
                        ->groupBy('audit_assessment_score_id');
                }])
                ->where('fiscal_year_id',$request->fiscal_year_id)
                ->orderBy('id','DESC')
                ->paginate(config('bee_config.per_page_pagination'));
            return ['status' => 'success', 'data' => $responseData];
        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }

    }

    public function edit(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {
            $responseData = AuditAssessmentScore::with(['fiscal_year','audit_assessment_score_items.criteria'])
                ->addSelect(['total_score' => function ($query) {
                    $query->select(\DB::raw('sum(score)'))
                        ->from('audit_assessment_score_items')
                        ->whereColumn('audit_assessment_score_id', 'audit_assessment_scores.id')
                        ->groupBy('audit_assessment_score_id');
                }])
                ->where('id',$request->audit_assessment_score_id)
                ->first();
            return ['status' => 'success', 'data' => $responseData];
        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }
    }


    public function auditMemoUpdate(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        \DB::beginTransaction();
        try {
            $audit_memo = AcMemo::find($request->memo_id);
            $audit_memo->memo_title_bn = $request->memo_title_bn;
            $audit_memo->memo_description_bn = $request->memo_description_bn;
            $audit_memo->irregularity_cause = $request->irregularity_cause;
            $audit_memo->response_of_rpu = $request->response_of_rpu;
            $audit_memo->jorito_ortho_poriman = $request->jorito_ortho_poriman;
            $audit_memo->audit_year_start = $request->audit_year_start;
            $audit_memo->audit_year_end = $request->audit_year_end;
            $audit_memo->memo_irregularity_type = $request->memo_irregularity_type;
            $audit_memo->memo_irregularity_sub_type = $request->memo_irregularity_sub_type;
            $audit_memo->memo_type = $request->memo_type;
            $audit_memo->memo_status = $request->memo_status;
            $audit_memo->updated_by = $cdesk->officer_id;

            $changes = array();
            foreach ($audit_memo->getDirty() as $key => $value) {
                $original = $audit_memo->getOriginal($key);
                $changes[$key] = [
                    'old' => $original,
                    'new' => $value,
                ];
            }

            $memo_log = new AcMemoLog();
            $memo_log->memo_content_change = json_encode($changes);
            $memo_log->memo_id = $request->memo_id;
            $memo_log->modified_by_id = $cdesk->officer_id;
            $memo_log->modified_by_name_bn = $cdesk->officer_bn;
            $memo_log->modified_by_name_en = $cdesk->officer_en;
            $memo_log->save();

            $audit_memo->save();

            //for attachments
            $finalAttachments = [];

            //for porisishtos
            if ($request->hasfile('porisishtos')) {
                foreach ($request->porisishtos as $file){
                    $userDefineFileName = $file->getClientOriginalName();
                    $fileName = 'porisishto_'.uniqid() . '.' . $file->extension();

                    Storage::disk('public')->put('memo/dicfia/' . $fileName, File::get($file));
                    array_push($finalAttachments, array(
                            'ac_memo_id' => $audit_memo->id,
                            'attachment_type' => 'porisishto',
                            'user_define_name' => $userDefineFileName,
                            'attachment_name' => $fileName,
                            'attachment_path' => url('storage/memo/dicfia/' . $fileName),
                            'sequence' => 1,
                            'created_by' => $cdesk->officer_id,
                            'modified_by' => $cdesk->officer_id,
                        )
                    );
                }
            }

            //for pramanoks
            if ($request->hasfile('pramanoks')) {
                foreach ($request->pramanoks as $file){
                    $userDefineFileName = $file->getClientOriginalName();
                    $fileName = 'pramanok_'.uniqid() . '.' . $file->extension();

                    Storage::disk('public')->put('memo/dicfia/' . $fileName, File::get($file));

                    array_push($finalAttachments, array(
                            'ac_memo_id' => $audit_memo->id,
                            'attachment_type' => 'pramanok',
                            'user_define_name' => $userDefineFileName,
                            'attachment_name' => $fileName,
                            'attachment_path' => url('storage/memo/dicfia/' . $fileName),
                            'sequence' => 1,
                            'created_by' => $cdesk->officer_id,
                            'modified_by' => $cdesk->officer_id,
                        )
                    );
                }
            }

            //for memos
            if ($request->hasfile('memos')) {
                foreach ($request->memos as $file){
                    $userDefineFileName = $file->getClientOriginalName();
                    $fileName = 'memo_'.uniqid() . '.' . $file->extension();

                    Storage::disk('public')->put('memo/dicfia/' . $fileName, File::get($file));

                    array_push($finalAttachments, array(
                            'ac_memo_id' => $audit_memo->id,
                            'attachment_type' => 'memo',
                            'user_define_name' => $userDefineFileName,
                            'attachment_name' => $fileName,
                            'attachment_path' => url('storage/memo/dicfia/' . $fileName),
                            'sequence' => 1,
                            'created_by' => $cdesk->officer_id,
                            'modified_by' => $cdesk->officer_id,
                        )
                    );
                }
            }
            AcMemoAttachment::insert($finalAttachments);

            $memo_info = AcMemo::with('ac_memo_attachments:id,ac_memo_id,attachment_type,user_define_name,attachment_path,sequence')->where('id', $request->memo_id)->first();
//            return ['status' => 'success', 'data' => $memo_info];
            if($memo_info->has_sent_to_rpu){
                $data['memo_info'] = $memo_info;
                $update_audit_memo_to_rpu = $this->initRPUHttp()->post(config('cag_rpu_api.update_memo_to_rpu'), $data)->json();
                if ($update_audit_memo_to_rpu['status'] == 'success') {
                    return ['status' => 'success', 'data' => 'Memo Update Successfully'];
                } else {
                    throw new \Exception(json_encode($update_audit_memo_to_rpu));
                }
            }else{
                return ['status' => 'success', 'data' => 'Memo Update Successfully'];
            }

        } catch (\Exception $exception) {
            \DB::rollback();
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }

    }

    public function sendMemoToRpu(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {

            $memo = AcMemo::with('ac_memo_attachments:id,ac_memo_id,attachment_type,user_define_name,attachment_path,sequence')
                ->whereIn('id', $request->memos)
                ->get();

            $data['memos'] = $memo;
            $data['memo_send_date'] = date('Y-m-d');
            $data['directorate_id'] = $cdesk->office_id;
            $data['directorate_en'] = $cdesk->office_name_en;
            $data['directorate_bn'] = $cdesk->office_name_bn;
            $data['sender_officer_id'] = $cdesk->officer_id;
            $data['sender_officer_id'] = $cdesk->officer_id;
            $data['sender_officer_name_bn'] = $cdesk->officer_bn;
            $data['sender_officer_name_en'] = $cdesk->officer_en;
            $data['sender_designation_id'] = $cdesk->designation_id;
            $data['sender_designation_en'] = $cdesk->designation_en;
            $data['sender_designation_bn'] = $cdesk->designation_bn;

            $send_audit_memo_to_rpu = $this->initRPUHttp()->post(config('cag_rpu_api.send_memo_to_rpu'), $data)->json();

            if ($send_audit_memo_to_rpu['status'] == 'success') {
                AcMemo::whereIn('id', $request->memos)
                    ->update([
                        'has_sent_to_rpu'=>1,
                        'sender_officer_id'=>$cdesk->officer_id,
                        'sender_officer_name_bn'=>$cdesk->officer_bn,
                        'sender_officer_name_en'=>$cdesk->officer_en,
                        'sender_unit_id'=>$cdesk->office_unit_id,
                        'sender_unit_name_bn'=>$cdesk->office_unit_bn,
                        'sender_unit_name_en'=>$cdesk->office_unit_en,
                        'sender_designation_id'=>$cdesk->designation_id,
                        'sender_designation_bn'=>$cdesk->designation_bn,
                        'sender_designation_en'=>$cdesk->designation_en
                    ]);

                $apotti_sequence = Apotti::max('apotti_sequence');

                foreach ($memo as $memo_item){
                   $apotti =  New Apotti();
                   $apotti->audit_plan_id = $memo_item['audit_plan_id'];
                   $apotti->onucched_no = $apotti_sequence + 1;
                   $apotti->apotti_title = $memo_item['memo_title_bn'];
                   $apotti->apotti_description = $memo_item['memo_description_bn'];
                   $apotti->ministry_id = $memo_item['ministry_id'];
                   $apotti->ministry_name_en = $memo_item['ministry_name_en'];
                   $apotti->ministry_name_bn = $memo_item['ministry_name_en'];
                   $apotti->parent_office_id = $memo_item['parent_office_id'];
                   $apotti->parent_office_name_en = $memo_item['parent_office_name_en'];
                   $apotti->parent_office_name_bn = $memo_item['parent_office_name_bn'];
                   $apotti->fiscal_year_id = $memo_item['fiscal_year_id'];
                   $apotti->total_jorito_ortho_poriman = $memo_item['jorito_ortho_poriman'];
                   $apotti->total_onishponno_jorito_ortho_poriman = $memo_item['onishponno_jorito_ortho_poriman'];
                   $apotti->created_by = $cdesk->officer_id;
                   $apotti->approve_status = 1;
                   $apotti->status = 0;
                   $apotti->apotti_sequence = $apotti_sequence + 1;
                   $apotti->is_combined = 0;
                   $apotti->save();

                   $apotti_item =  New ApottiItem();
                   $apotti_item->apotti_id = $apotti->id;
                   $apotti_item->memo_id = $memo_item['id'];
                   $apotti_item->onucched_no = 1;
                   $apotti_item->memo_irregularity_type = $memo_item['memo_irregularity_type'];
                   $apotti_item->memo_irregularity_sub_type = $memo_item['memo_irregularity_sub_type'];
                   $apotti_item->ministry_id = $memo_item['ministry_id'];
                   $apotti_item->ministry_name_en = $memo_item['ministry_name_en'];
                   $apotti_item->ministry_name_bn = $memo_item['ministry_name_en'];
                   $apotti_item->parent_office_id = $memo_item['parent_office_id'];
                   $apotti_item->parent_office_name_en = $memo_item['parent_office_name_en'];
                   $apotti_item->parent_office_name_bn = $memo_item['parent_office_name_bn'];
                   $apotti_item->cost_center_id = $memo_item['cost_center_id'];
                   $apotti_item->cost_center_name_en = $memo_item['cost_center_name_en'];
                   $apotti_item->cost_center_name_bn = $memo_item['cost_center_name_bn'];
                   $apotti_item->fiscal_year_id = $memo_item['fiscal_year_id'];
                   $apotti_item->audit_year_start = $memo_item['audit_year_start'];
                   $apotti_item->audit_year_end = $memo_item['audit_year_end'];
                   $apotti_item->ac_query_potro_no = $memo_item['ac_query_potro_no'];
                   $apotti_item->ap_office_order_id = $memo_item['ap_office_order_id'];
                   $apotti_item->audit_plan_id = $memo_item['audit_plan_id'];
                   $apotti_item->audit_type = $memo_item['audit_type'];
                   $apotti_item->team_id = $memo_item['team_id'];
                   $apotti_item->memo_title_bn = $memo_item['memo_title_bn'];
                   $apotti_item->memo_description_bn = $memo_item['memo_description_bn'];
                   $apotti_item->memo_title_bn = $memo_item['memo_title_bn'];
                   $apotti_item->memo_type = $memo_item['memo_type'];
                   $apotti_item->memo_status = $memo_item['memo_status'];
                   $apotti_item->jorito_ortho_poriman = $memo_item['jorito_ortho_poriman'];
                   $apotti_item->onishponno_jorito_ortho_poriman = $memo_item['onishponno_jorito_ortho_poriman'];
                   $apotti_item->created_by = $cdesk->officer_id;
                   $apotti_item->status = 0;
                   $apotti_item->save();
                }

                return ['status' => 'success', 'data' => 'Send Successfully'];
            } else {
                throw new \Exception(json_encode($send_audit_memo_to_rpu));
            }
        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }

    }

    public function authorityMemoList(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($request->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {
            $fiscal_year_id = $request->fiscal_year_id;
            $cost_center_id = $request->cost_center_id;
            $team_id = $request->team_id;
            $memo_irregularity_type= $request->memo_irregularity_type;
            $memo_irregularity_sub_type = $request->memo_irregularity_sub_type;
            $memo_type = $request->memo_type;
            $memo_status = $request->memo_status;
            $jorito_ortho_poriman = $request->jorito_ortho_poriman;
            $audit_year_start = $request->audit_year_start;
            $audit_year_end = $request->audit_year_end;

            $query = AcMemo::query();

            $query->when($fiscal_year_id, function ($q, $fiscal_year_id) {
                return $q->where('fiscal_year_id', $fiscal_year_id);
            });

            $query->when($cost_center_id, function ($q, $cost_center_id) {
                return $q->where('cost_center_id', $cost_center_id);
            });

            $query->when($team_id, function ($q, $team_id) {
                return $q->where('team_id', $team_id);
            });

            $query->when($memo_irregularity_type, function ($q, $memo_irregularity_type) {
                return $q->where('memo_irregularity_type', $memo_irregularity_type);
            });

            $query->when($memo_irregularity_sub_type, function ($q, $memo_irregularity_sub_type) {
                return $q->where('memo_irregularity_sub_type', $memo_irregularity_sub_type);
            });

            $query->when($memo_type, function ($q, $memo_type) {
                return $q->where('memo_type', $memo_type);
            });

            $query->when($memo_status, function ($q, $memo_status) {
                return $q->where('memo_status', $memo_status);
            });

            $query->when($jorito_ortho_poriman, function ($q, $jorito_ortho_poriman) {
                return $q->where('jorito_ortho_poriman', $jorito_ortho_poriman);
            });

            $query->when($audit_year_start, function ($q, $audit_year_start) {
                return $q->where('audit_year_start', $audit_year_start);
            });

            $query->when($audit_year_end, function ($q, $audit_year_end) {
                return $q->where('audit_year_end', $audit_year_end);
            });

            $memo_list['memo_list'] = $query->with(['ac_memo_attachments'])
                ->paginate(config('bee_config.per_page_pagination'));

            $memo_list['total_memo'] = AcMemo::count('id');

            return ['status' => 'success', 'data' => $memo_list];

        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }

    }

    public function auditMemoRecommendationStore(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        \DB::beginTransaction();
        try {
            $audit_memo_recommendaton = new AcMemoRecommendation();
            $audit_memo_recommendaton->memo_id = $request->memo_id;
            $audit_memo_recommendaton->audit_recommendation = $request->audit_recommendation;
            $audit_memo_recommendaton->created_by = $cdesk->officer_id;
            $audit_memo_recommendaton->created_by_name_en = $cdesk->officer_en;
            $audit_memo_recommendaton->created_by_name_bn = $cdesk->officer_bn;
            $audit_memo_recommendaton->save();

            AcMemo::where('id',$request->memo_id)->update(['audit_recommendation' => $request->audit_recommendation]);

            return ['status' => 'success', 'data' => 'Memo Recommendation Successfully'];

        } catch (\Exception $exception) {
            \DB::rollback();
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }

    }

    public function responseOfRpuMemo(Request $request): array
    {
        $office_db_con_response = $this->switchOffice($request->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {
            $ac_memo = AcMemo::find($request->memo_id);
            $ac_memo->response_of_rpu = $request->response_of_rpu;
            $ac_memo->save();

            return ['status' => 'success', 'data' => 'Response Send Successfully'];

        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }
    }

    public function acknowledgmentOfRpuMemo(Request $request): array
    {
        $office_db_con_response = $this->switchOffice($request->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {
            $ac_memo = AcMemo::find($request->memo_id);
            $ac_memo->rpu_acceptor_officer_id = $request->rpu_acceptor_officer_id;
            $ac_memo->rpu_acceptor_officer_name_bn = $request->rpu_acceptor_officer_name_bn;
            $ac_memo->rpu_acceptor_officer_name_en = $request->rpu_acceptor_officer_name_en;
            $ac_memo->rpu_acceptor_unit_name_bn = $request->rpu_acceptor_unit_name_bn;
            $ac_memo->rpu_acceptor_unit_name_en = $request->rpu_acceptor_unit_name_en;
            $ac_memo->rpu_acceptor_designation_name_bn = $request->rpu_acceptor_designation_name_bn;
            $ac_memo->rpu_acceptor_designation_name_en = $request->rpu_acceptor_designation_name_en;
            $ac_memo->rpu_acceptor_signature = $request->rpu_acceptor_signature;
            $ac_memo->save();

            return ['status' => 'success', 'data' => 'Response Send Successfully'];

        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }
    }

    public function auditMemoRecommendationList(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {
            $audit_memo_recommendaton_list = AcMemoRecommendation::all();
            return ['status' => 'success', 'data' => $audit_memo_recommendaton_list];

        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }

    }

    public function auditMemoLogList(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {
            $audit_memo_log_list = AcMemoLog::where('memo_id',$request->memo_id)->paginate(config('bee_config.per_page_pagination'));
            return ['status' => 'success', 'data' => $audit_memo_log_list];

        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }

    }

    public function attachmentList(Request $request): array
    {
        $cdesk = json_decode($request->cdesk, false);
        $office_db_con_response = $this->switchOffice($cdesk->office_id);
        if (!isSuccessResponse($office_db_con_response)) {
            return ['status' => 'error', 'data' => $office_db_con_response];
        }
        try {
            $data['porisishtos'] = AcMemoAttachment::where('ac_memo_id',$request->memo_id)->where('attachment_type','porisishto')->get()->toArray();
            $data['pramanoks'] = AcMemoAttachment::where('ac_memo_id',$request->memo_id)->where('attachment_type','pramanok')->get()->toArray();
            $data['memos'] = AcMemoAttachment::where('ac_memo_id',$request->memo_id)->where('attachment_type','memo')->get()->toArray();
            return ['status' => 'success', 'data' => $data];

        } catch (\Exception $exception) {
            return ['status' => 'error', 'data' => $exception->getMessage()];
        }

    }
}