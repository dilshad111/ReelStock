<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_card_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->unsignedInteger('version_no');
            $table->string('change_request_no')->nullable()->unique();
            $table->string('change_reason')->nullable();
            $table->date('effective_date')->nullable();
            $table->enum('approval_status', ['Draft', 'Pending', 'Approved', 'Rejected'])->default('Approved');
            $table->enum('version_status', ['Active', 'Superseded'])->default('Active');
            $table->json('snapshot_data');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['job_card_id', 'version_no']);
            $table->index(['job_card_id', 'version_status']);
        });

        Schema::create('job_card_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_version_id')->constrained('job_card_versions')->cascadeOnDelete();
            $table->string('field_name');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('modified_at')->nullable();
            $table->timestamps();

            $table->index(['job_card_version_id', 'field_name']);
        });

        Schema::table('job_cards', function (Blueprint $table) {
            $table->unsignedInteger('current_version_no')->default(1)->after('job_card_no');
            $table->foreignId('active_version_id')->nullable()->after('current_version_no')->constrained('job_card_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_version_id');
            $table->dropColumn('current_version_no');
        });

        Schema::dropIfExists('job_card_change_logs');
        Schema::dropIfExists('job_card_versions');
    }
};
