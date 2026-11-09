<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stall_mappings', function (Blueprint $table) {
            $table->text('renewal_note')->nullable()->after('requested_end_date');
            $table->text('renewal_rejection_reason')->nullable()->after('renewal_note');
        });
    }

    public function down(): void
    {
        Schema::table('stall_mappings', function (Blueprint $table) {
            $table->dropColumn(['renewal_note', 'renewal_rejection_reason']);
        });
    }
};
