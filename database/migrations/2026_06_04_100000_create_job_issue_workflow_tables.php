<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_issues', function (Blueprint $table) {
            $table->id();
            $table->string('job_no')->unique();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('purchase_order_no')->nullable();
            $table->decimal('required_carton_qty', 15, 2);
            $table->date('issued_date');
            $table->string('production_route', 40)->default('print_die_cut');
            $table->string('current_stage', 40)->default('Corrugation');
            $table->string('status', 30)->default('Issued');
            $table->decimal('final_finished_qty', 15, 2)->default(0);
            $table->decimal('rejected_cartons_qty', 15, 2)->default(0);
            $table->string('final_wastage_reason')->nullable();
            $table->text('completion_remarks')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index(['job_card_id', 'status']);
            $table->index(['current_stage', 'status']);
        });

        Schema::create('job_issue_stage_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_issue_id')->constrained('job_issues')->cascadeOnDelete();
            $table->string('stage', 40);
            $table->foreignId('machine_id')->nullable()->constrained('production_machines')->nullOnDelete();
            $table->string('machine_name')->nullable();
            $table->foreignId('operator_id')->nullable()->constrained('machine_operators')->nullOnDelete();
            $table->string('operator_name')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->decimal('quantity_produced', 15, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['job_issue_id', 'stage']);
            $table->index(['machine_id', 'operator_id']);
        });

        Schema::create('job_issue_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_issue_id')->constrained('job_issues')->cascadeOnDelete();
            $table->foreignId('stage_entry_id')->nullable()->constrained('job_issue_stage_entries')->cascadeOnDelete();
            $table->string('stage', 40);
            $table->dateTime('breakdown_start_at');
            $table->dateTime('breakdown_end_at')->nullable();
            $table->string('reason');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['job_issue_id', 'stage']);
        });

        Schema::create('job_issue_wastages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_issue_id')->constrained('job_issues')->cascadeOnDelete();
            $table->foreignId('stage_entry_id')->nullable()->constrained('job_issue_stage_entries')->cascadeOnDelete();
            $table->string('stage', 40);
            $table->string('wastage_type');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->string('reason')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['job_issue_id', 'stage']);
            $table->index('reason');
        });

        Schema::create('job_issue_reel_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_issue_id')->constrained('job_issues')->cascadeOnDelete();
            $table->foreignId('stage_entry_id')->nullable()->constrained('job_issue_stage_entries')->cascadeOnDelete();
            $table->foreignId('reel_id')->nullable()->constrained('reels')->nullOnDelete();
            $table->string('reel_no');
            $table->foreignId('paper_quality_id')->nullable()->constrained('paper_qualities')->nullOnDelete();
            $table->string('quality_name')->nullable();
            $table->decimal('consumed_weight', 15, 2);
            $table->timestamps();

            $table->index(['job_issue_id', 'reel_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_issue_reel_consumptions');
        Schema::dropIfExists('job_issue_wastages');
        Schema::dropIfExists('job_issue_breakdowns');
        Schema::dropIfExists('job_issue_stage_entries');
        Schema::dropIfExists('job_issues');
    }
};
