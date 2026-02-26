<?php

namespace App\Exceptions;

use Exception;

class BulkImportDuplicateException extends Exception
{
    // Used specifically to catch duplicate entries gracefully during bulk user import
}
