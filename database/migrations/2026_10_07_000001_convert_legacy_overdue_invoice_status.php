<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Overdue" is computed (pending and past due), never stored. Any invoice still carrying the
     * old stored status becomes pending again so checkout, filters and badges treat it correctly.
     */
    public function up(): void
    {
        DB::table('invoices')->where('status', 'overdue')->update(['status' => 'pending']);
    }

    public function down(): void
    {
        // Not reversible: which rows were stored as "overdue" is no longer known, and pending is the correct state.
    }
};
