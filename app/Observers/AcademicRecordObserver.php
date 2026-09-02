<?php

namespace App\Observers;

use App\Models\AcademicRecord;
use App\Support\AuditLogger;

/**
 * Records every mutation of an academic record.
 *
 * Living on the model rather than the controller means a change made through
 * tinker, a console command or a future API is audited just the same.
 */
class AcademicRecordObserver
{
    public function created(AcademicRecord $record): void
    {
        AuditLogger::log(AuditLogger::RECORD_CREATED, $record);
    }

    public function updated(AcademicRecord $record): void
    {
        AuditLogger::log(AuditLogger::RECORD_UPDATED, $record);
    }

    public function deleted(AcademicRecord $record): void
    {
        AuditLogger::log(AuditLogger::RECORD_DELETED, $record);
    }
}
