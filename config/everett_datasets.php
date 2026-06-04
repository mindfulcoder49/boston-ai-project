<?php

return [
    'arrest_log_page_url_template' => 'https://www.everettpolicema.com/arrest_log_{year}/arrest_log.php',
    'daily_log_page_url_template'  => 'https://www.everettpolicema.com/daily_log_{year}/daily_log.php',
    'years_to_process'             => [2021, 2022, 2023, 2024, 2025, 2026],
    'fail_on_pdf_conversion_failure' => env('EVERETT_FAIL_ON_PDF_CONVERSION_FAILURE', false),
    'pdfs_directory'               => 'pdfs',
    'markdown_output_directory'    => 'markdown_output',
    'html_pages_directory'         => 'html_pages',
];
