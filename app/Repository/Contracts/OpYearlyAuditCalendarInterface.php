<?php

namespace App\Repository\Contracts;

use Illuminate\Http\Request;

interface OpYearlyAuditCalendarInterface
{
    public function allCalendarLists(Request $request);
}