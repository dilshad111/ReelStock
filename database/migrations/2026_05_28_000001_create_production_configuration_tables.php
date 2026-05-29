<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printing_colors', function (Blueprint $table) {
            $table->id();
            $table->string('ink_code', 50)->unique();
            $table->string('ink_name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('department_name')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('production_machines', function (Blueprint $table) {
            $table->id();
            $table->string('machine_name');
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->decimal('base_speed', 12, 2)->nullable();
            $table->decimal('minimum_speed', 12, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['machine_name', 'department_id']);
        });

        Schema::create('machine_operators', function (Blueprint $table) {
            $table->id();
            $table->string('operator_name');
            $table->foreignId('machine_id')->constrained('production_machines')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['operator_name', 'machine_id']);
        });

        Schema::create('optimization_rules', function (Blueprint $table) {
            $table->id();
            $table->string('parameter_name');
            $table->string('condition_field', 50);
            $table->string('operator', 5);
            $table->string('condition_value');
            $table->string('adjustment_type', 50);
            $table->decimal('adjustment_value', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['condition_field', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('optimization_rules');
        Schema::dropIfExists('machine_operators');
        Schema::dropIfExists('production_machines');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('printing_colors');
    }
};
