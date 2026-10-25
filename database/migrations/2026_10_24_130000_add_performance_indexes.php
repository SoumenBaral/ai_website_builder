<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            // Dashboard: "events of this project, of this kind, in this period".
            $table->index(['project_id', 'event', 'created_at'], 'analytics_project_event_time');
        });
        Schema::table('pages', function (Blueprint $table) {
            $table->index(['project_id', 'is_home'], 'pages_project_home');
        });
        Schema::table('page_versions', function (Blueprint $table) {
            $table->index(['page_id', 'created_at'], 'page_versions_page_time');
        });
    }

    public function down(): void
    {
        Schema::table('analytics_events', fn (Blueprint $t) => $t->dropIndex('analytics_project_event_time'));
        Schema::table('pages', fn (Blueprint $t) => $t->dropIndex('pages_project_home'));
        Schema::table('page_versions', fn (Blueprint $t) => $t->dropIndex('page_versions_page_time'));
    }
};
