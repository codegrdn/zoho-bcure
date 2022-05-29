<?php

namespace App\Helpers;

use phpDocumentor\Reflection\Utils;
use zcrmsdk\crm\crud\ZCRMRecord;

class ZCRMRecordHelper
{
    public static function getFieldValue($record, $field, $fallbackValue = null)
    {
        if ($record) {
            return $record->getFieldValue($field) ? $record->getFieldValue($field) : $fallbackValue;
        } else {
            return $fallbackValue;
        }
    }
}