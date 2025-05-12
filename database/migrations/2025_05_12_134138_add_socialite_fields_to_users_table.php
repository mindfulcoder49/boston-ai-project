<?php

// In the new migration file (e.g., xxxx_xx_xx_xxxxxx_add_socialite_fields_to_users_table.php)
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider_id')->nullable()->after('id'); // For the OAuth provider's user ID
            $table->string('provider_name')->nullable()->after('provider_id'); // To store which provider (e.g., 'google', 'github')
            $table->text('provider_avatar')->nullable()->after('email'); // Optional: for social avatar
            $table->string('password')->nullable()->change(); // Make password nullable for social-only users
            // You might also want to add provider_token, provider_refresh_token if you need to make API calls later
            // $table->text('provider_token')->nullable();
            // $table->text('provider_refresh_token')->nullable();

            // Add unique constraint if you want to ensure provider_id is unique per provider
            // $table->unique(['provider_name', 'provider_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['provider_id', 'provider_name', 'provider_avatar']);
            $table->string('password')->nullable(false)->change(); // Revert password nullability
            // $table->dropColumn(['provider_token', 'provider_refresh_token']);
        });
    }
};
