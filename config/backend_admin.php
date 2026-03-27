<?php

$gb = 1024 * 1024 * 1024;

return [
    'pipeline_runs' => [
        'root_path' => storage_path('logs/pipeline_runs'),
        'history_path' => storage_path('logs/pipeline_runs_history.json'),
        'stale_running_after_minutes' => (int) env('BACKEND_ADMIN_STALE_RUNNING_AFTER_MINUTES', 120),
    ],

    'daily_pipeline' => [
        'time' => env('BACKEND_ADMIN_DAILY_PIPELINE_TIME', '02:15'),
        'timezone' => env('BACKEND_ADMIN_TIMEZONE', config('app.timezone', 'America/New_York')),
        'active_run_max_age_hours' => (int) env('BACKEND_ADMIN_ACTIVE_RUN_MAX_AGE_HOURS', 6),
    ],

    'long_running_queue' => env('BACKEND_ADMIN_LONG_QUEUE', 'admin-long'),

    'queue_worker' => [
        'queues' => env(
            'BACKEND_ADMIN_WORKER_QUEUES',
            trim(env('BACKEND_ADMIN_LONG_QUEUE', 'admin-long') . ',default', ',')
        ),
        'timeout' => (int) env('BACKEND_ADMIN_QUEUE_WORKER_TIMEOUT', 7200),
        'tries' => (int) env('BACKEND_ADMIN_QUEUE_WORKER_TRIES', 1),
    ],

    'dependency_health' => [
        'snapshot_path' => storage_path('app/backend_admin/ingestion_dependency_health.json'),
        'scraper_timeout_seconds' => (int) env('BACKEND_ADMIN_SCRAPER_TIMEOUT_SECONDS', 5),
        'worker_heartbeat_path' => storage_path('app/backend_admin/admin_long_worker_heartbeat.json'),
        'worker_heartbeat_max_age_minutes' => (int) env('BACKEND_ADMIN_WORKER_HEARTBEAT_MAX_AGE_MINUTES', 180),
        'dns_status_s3_key' => env('BACKEND_ADMIN_DNS_STATUS_S3_KEY', 'ops/health/ec2_dns_status.json'),
        'dns_status_max_age_minutes' => (int) env('BACKEND_ADMIN_DNS_STATUS_MAX_AGE_MINUTES', 60),
        'scraper_health_path' => env('BACKEND_ADMIN_SCRAPER_HEALTH_PATH', 'health'),
    ],

    'alerts' => [
        'state_path' => storage_path('app/backend_admin/backend_health_alert_state.json'),
        'email' => env('BACKEND_ADMIN_ALERT_EMAIL', env('ADMIN_EMAIL', 'admin@example.com')),
        'success_window_hours' => (int) env('BACKEND_ADMIN_SUCCESS_WINDOW_HOURS', 24),
    ],

    'metrics' => [
        'specific_metrics_max_records' => (int) env('BACKEND_ADMIN_METRICS_SPECIFIC_MAX_RECORDS', 500000),
    ],

    'storage' => [
        'targets' => [
            [
                'slug' => 'pipeline_runs',
                'label' => 'Pipeline Run Logs',
                'path' => storage_path('logs/pipeline_runs'),
                'warning_bytes' => (int) env('BACKEND_ADMIN_PIPELINE_RUN_WARNING_BYTES', 5 * $gb),
            ],
            [
                'slug' => 'datasets',
                'label' => 'Downloaded Datasets',
                'path' => storage_path('app/datasets'),
                'warning_bytes' => (int) env('BACKEND_ADMIN_DATASET_WARNING_BYTES', 20 * $gb),
            ],
            [
                'slug' => 'laravel_log',
                'label' => 'Laravel Log File',
                'path' => storage_path('logs/laravel.log'),
                'warning_bytes' => (int) env('BACKEND_ADMIN_LARAVEL_LOG_WARNING_BYTES', 1 * $gb),
            ],
        ],
    ],
];
