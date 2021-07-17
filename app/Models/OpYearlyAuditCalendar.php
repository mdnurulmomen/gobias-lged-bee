<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpYearlyAuditCalendar extends Model
{
    use HasFactory;

    protected $fillable = [
        'duration_id',
        'fiscal_year_id',
        'employee_record_id',
        'initiator_name_en',
        'initiator_name_bn',
        'initiator_unit_name_en',
        'initiator_unit_name_bn',
        'cdesk_name_en',
        'cdesk_name_bn',
        'cdesk_unit_name_en',
        'cdesk_unit_name_bn',
        'status',
    ];

    public function fiscal_year()
    {
        return $this->belongsTo(XFiscalYear::class, 'fiscal_year_id', 'id');
    }

    public function calendar_movements()
    {
        return $this->hasMany(OpYearlyAuditCalendarMovement::class, 'op_yearly_calendar_id', 'id');
    }
}
