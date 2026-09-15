<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Upload limits
    |--------------------------------------------------------------------------
    */

    'max_upload_kb' => (int) env('TOOLS_MAX_UPLOAD_KB', 15360), // keep under php.ini's upload/post limits

    'max_files' => (int) env('TOOLS_MAX_FILES', 20),

    /*
    |--------------------------------------------------------------------------
    | Process timeout (seconds)
    |--------------------------------------------------------------------------
    |
    | Applies to any shelled-out binary (soffice, qpdf, ghostscript).
    |
    */

    'process_timeout' => (int) env('TOOLS_PROCESS_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | System binaries
    |--------------------------------------------------------------------------
    |
    | These are external programs, not composer packages. Install them on
    | the machine running this app:
    |
    |  - LibreOffice (soffice)  -> https://www.libreoffice.org/download/
    |  - qpdf                   -> https://qpdf.sourceforge.io / choco install qpdf
    |  - Ghostscript (gs)       -> https://ghostscript.com/releases / choco install ghostscript
    |
    */

    'binaries' => [
        'soffice' => env('SOFFICE_BINARY', 'soffice'),
        'qpdf' => env('QPDF_BINARY', 'qpdf'),
        'ghostscript' => env('GHOSTSCRIPT_BINARY', 'gs'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk broken-link checker
    |--------------------------------------------------------------------------
    */

    'link_checker' => [
        'max_urls' => (int) env('LINK_CHECKER_MAX_URLS', 200),
        'timeout' => (int) env('LINK_CHECKER_TIMEOUT', 10),
        'concurrency' => (int) env('LINK_CHECKER_CONCURRENCY', 10),
    ],

];
