<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read'], 'idx_notifications_user_read');
        });

        Schema::table('two_factor_codes', function (Blueprint $table) {
            $table->index('expires_at', 'idx_2fa_expires_at');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['entity_type', 'entity_id'], 'idx_audit_entity');
        });

        if (Schema::hasTable('utility_readings')) {
            Schema::table('utility_readings', function (Blueprint $table) {
                $table->index(['stall_id', 'type'], 'idx_utility_stall_type');
            });
        }
    }

    public function down(): void
    {
        Schema::table('notifications', fn($t) => $t->dropIndex('idx_notifications_user_read'));
        Schema::table('two_factor_codes', fn($t) => $t->dropIndex('idx_2fa_expires_at'));
        Schema::table('audit_logs', fn($t) => $t->dropIndex('idx_audit_entity'));
        if (Schema::hasTable('utility_readings')) {
            Schema::table('utility_readings', fn($t) => $t->dropIndex('idx_utility_stall_type'));
        }
    }
};
