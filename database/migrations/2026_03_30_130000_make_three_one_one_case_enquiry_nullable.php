<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE three_one_one_cases MODIFY case_enquiry_id BIGINT NULL');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('UPDATE three_one_one_cases SET case_enquiry_id = 0 WHERE case_enquiry_id IS NULL');
            DB::statement('ALTER TABLE three_one_one_cases MODIFY case_enquiry_id BIGINT NOT NULL');
        }
    }
};
