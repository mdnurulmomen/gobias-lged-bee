<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpOrganizationYearlyAuditCalendarEventSchedule extends Model
{
    use HasFactory;

    protected $connection = 'OfficeDB';

    protected $fillable = [
        'duration_id',
        'fiscal_year_id',
        'outcome_id',
        'output_id',
        'activity_id',
        'activity_title_en',
        'activity_title_bn',
        'activity_responsible_id',
        'activity_milestone_id',
        'op_yearly_audit_calendar_activity_id',
        'op_yearly_audit_calendar_id',
        'milestone_title_en',
        'milestone_title_bn',
        'milestone_target',
    ];
}
