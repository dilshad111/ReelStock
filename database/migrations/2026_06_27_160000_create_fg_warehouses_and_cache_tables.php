<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Create warehouses table
        if (!Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->id();
                $table->string('code', 30)->unique();
                $table->string('name', 100);
                $table->boolean('allow_negative_stock')->default(false);
                $table->string('status', 20)->default('Active');
                $table->timestamps();
            });
        }

        // 2. Seed a default warehouse if none exist
        $defaultWarehouse = DB::table('warehouses')->where('code', 'WH001')->first();
        if (!$defaultWarehouse) {
            $defaultWarehouseId = DB::table('warehouses')->insertGetId([
                'code' => 'WH001',
                'name' => 'Main Warehouse',
                'allow_negative_stock' => false,
                'status' => 'Active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $defaultWarehouseId = $defaultWarehouse->id;
        }

        // 3. Create current_fg_stock table
        if (!Schema::hasTable('current_fg_stock')) {
            Schema::create('current_fg_stock', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->decimal('quantity', 12, 4)->default(0.0000);
                $table->timestamp('last_updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->unique(['product_id', 'warehouse_id']);
            });
        }

        // 4. Create inventory_audit_log table
        if (!Schema::hasTable('inventory_audit_log')) {
            Schema::create('inventory_audit_log', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('action_timestamp')->useCurrent();
                $table->string('transaction_type', 30);
                $table->string('document_number', 100);
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->decimal('old_quantity', 12, 4);
                $table->decimal('new_quantity', 12, 4);
                $table->text('reason_for_change');
                $table->string('ip_address', 45)->nullable();
            });
        }

        // 5. Add warehouse_id to fg_receipts
        if (!Schema::hasColumn('fg_receipts', 'warehouse_id')) {
            Schema::table('fg_receipts', function (Blueprint $table) {
                $table->foreignId('warehouse_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('warehouses')
                    ->cascadeOnDelete();
            });
        }

        // Backfill fg_receipts with default warehouse
        DB::table('fg_receipts')->whereNull('warehouse_id')->update(['warehouse_id' => $defaultWarehouseId]);

        // ALTER to not null
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE fg_receipts MODIFY warehouse_id BIGINT UNSIGNED NOT NULL');
        }

        // 6. Add warehouse_id to fg_dispatches
        if (!Schema::hasColumn('fg_dispatches', 'warehouse_id')) {
            Schema::table('fg_dispatches', function (Blueprint $table) {
                $table->foreignId('warehouse_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('warehouses')
                    ->cascadeOnDelete();
            });
        }

        // Backfill fg_dispatches with default warehouse
        DB::table('fg_dispatches')->whereNull('warehouse_id')->update(['warehouse_id' => $defaultWarehouseId]);

        // ALTER to not null
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE fg_dispatches MODIFY warehouse_id BIGINT UNSIGNED NOT NULL');
        }

        // 7. Add warehouse_id, document_number, created_by and remarks to fg_stock_ledger
        if (!Schema::hasColumn('fg_stock_ledger', 'warehouse_id')) {
            Schema::table('fg_stock_ledger', function (Blueprint $table) {
                $table->foreignId('warehouse_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('warehouses')
                    ->cascadeOnDelete();
            });
        }
        if (!Schema::hasColumn('fg_stock_ledger', 'document_number')) {
            Schema::table('fg_stock_ledger', function (Blueprint $table) {
                $table->string('document_number', 100)->nullable()->after('transaction_type');
            });
        }
        if (!Schema::hasColumn('fg_stock_ledger', 'created_by')) {
            Schema::table('fg_stock_ledger', function (Blueprint $table) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('transaction_date')
                    ->constrained('users')
                    ->cascadeOnDelete();
            });
        }
        if (!Schema::hasColumn('fg_stock_ledger', 'remarks')) {
            Schema::table('fg_stock_ledger', function (Blueprint $table) {
                $table->text('remarks')->nullable()->after('created_by');
            });
        }

        // Backfill fg_stock_ledger with default warehouse and default user
        $defaultUserId = DB::table('users')->orderBy('id')->first()?->id ?? 1;
        DB::table('fg_stock_ledger')->whereNull('warehouse_id')->update(['warehouse_id' => $defaultWarehouseId]);
        DB::table('fg_stock_ledger')->whereNull('created_by')->update(['created_by' => $defaultUserId]);

        // Backfill document_number in fg_stock_ledger from reference tables where possible
        $ledgerEntries = DB::table('fg_stock_ledger')->whereNull('document_number')->get();
        foreach ($ledgerEntries as $entry) {
            $docNum = 'OPENING';
            if ($entry->transaction_type === 'receipt') {
                $docNum = 'RCPT-' . $entry->reference_id;
            } elseif ($entry->transaction_type === 'dispatch') {
                $dispatch = DB::table('fg_dispatches')->where('id', $entry->reference_id)->first();
                $docNum = $dispatch ? $dispatch->dc_number : ('DSP-' . $entry->reference_id);
            }
            DB::table('fg_stock_ledger')
                ->where('id', $entry->id)
                ->update(['document_number' => $docNum]);
        }

        // For any remaining null document numbers
        DB::table('fg_stock_ledger')->whereNull('document_number')->update(['document_number' => 'OPENING']);

        // ALTER column types and nullability using raw SQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE fg_stock_ledger MODIFY warehouse_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE fg_stock_ledger MODIFY document_number VARCHAR(100) NOT NULL');
            DB::statement('ALTER TABLE fg_stock_ledger MODIFY transaction_type VARCHAR(30) NOT NULL');
            DB::statement('ALTER TABLE fg_stock_ledger MODIFY quantity_in DECIMAL(12, 4) NOT NULL DEFAULT 0.0000');
            DB::statement('ALTER TABLE fg_stock_ledger MODIFY quantity_out DECIMAL(12, 4) NOT NULL DEFAULT 0.0000');
            DB::statement('ALTER TABLE fg_stock_ledger MODIFY balance_after DECIMAL(12, 4) NOT NULL DEFAULT 0.0000');
            DB::statement('ALTER TABLE fg_stock_ledger MODIFY created_by BIGINT UNSIGNED NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fg_stock_ledger', function (Blueprint $table) {
            if (Schema::hasColumn('fg_stock_ledger', 'warehouse_id')) {
                $table->dropConstrainedForeignId('warehouse_id');
            }
            if (Schema::hasColumn('fg_stock_ledger', 'document_number')) {
                $table->dropColumn('document_number');
            }
            if (Schema::hasColumn('fg_stock_ledger', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            if (Schema::hasColumn('fg_stock_ledger', 'remarks')) {
                $table->dropColumn('remarks');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE fg_stock_ledger MODIFY transaction_type ENUM("opening", "receipt", "dispatch", "adjustment") NOT NULL');
            DB::statement('ALTER TABLE fg_stock_ledger MODIFY quantity_in DECIMAL(12, 2) NOT NULL DEFAULT 0.00');
            DB::statement('ALTER TABLE fg_stock_ledger MODIFY quantity_out DECIMAL(12, 2) NOT NULL DEFAULT 0.00');
            DB::statement('ALTER TABLE fg_stock_ledger MODIFY balance_after DECIMAL(12, 2) NOT NULL DEFAULT 0.00');
        }

        Schema::table('fg_dispatches', function (Blueprint $table) {
            if (Schema::hasColumn('fg_dispatches', 'warehouse_id')) {
                $table->dropConstrainedForeignId('warehouse_id');
            }
        });

        Schema::table('fg_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('fg_receipts', 'warehouse_id')) {
                $table->dropConstrainedForeignId('warehouse_id');
            }
        });

        Schema::dropIfExists('inventory_audit_log');
        Schema::dropIfExists('current_fg_stock');
        Schema::dropIfExists('warehouses');
    }
};
