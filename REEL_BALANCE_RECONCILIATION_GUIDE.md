# REEL BALANCE RECONCILIATION SYSTEM
**Date:** 2026-01-22  
**Author:** Antigravity AI  
**Version:** 1.0

---

## OVERVIEW

This document describes the comprehensive solution implemented to prevent and auto-correct reel stock balance discrepancies in the ReelStock application.

---

## PROBLEM STATEMENT

The Reel Stock Report balances were showing discrepancies for certain reels:

| Reel No. | Actual Balance Weight | Issue |
|----------|----------------------|-------|
| RL111616 | 550 Kg | Incorrect balance |
| RL111620 | 150 Kg | Incorrect balance |
| RL111626 | 0 Kg | Should have balance |
| RL111627 | 0 Kg | Should have balance |

**Root Cause:** The `balance_weight` field in the `reels` table was not always synchronized with the transaction history (issues and returns), causing the stored balance to drift from the actual calculated balance.

---

## SOLUTION COMPONENTS

### 1. IMMEDIATE FIX - SQL Correction Script

**File:** `database/scripts/fix_reel_balances_20260122.sql`

This script:
- ✅ Analyzes current vs. calculated balances for problem reels
- ✅ Shows detailed transaction history
- ✅ Provides manual correction SQL (commented out for safety)
- ✅ Includes verification queries

**Usage:**
```bash
# Review the discrepancies first
mysql -u username -p database_name < database/scripts/fix_reel_balances_20260122.sql

# After review, uncomment Step 4 in the SQL file and run again to apply fixes
```

---

### 2. CONTROLLER VALIDATION - Real-time Synchronization

**Files:** 
- `app/Http/Controllers/ReelIssueController.php`
- `app/Http/Controllers/ReelReturnController.php`

**Enhancement:**
Added `validateAndSyncBalance()` method to both controllers that:
- ✅ Calculates the correct balance from transaction history
- ✅ Compares it with the stored `balance_weight`
- ✅ Auto-corrects discrepancies > 0.01 kg
- ✅ Logs all auto-corrections for audit trail
- ✅ Runs automatically on every issue/return transaction

**Implementation:**
```php
protected function validateAndSyncBalance(Reel $reel): bool
{
    $totalConsumed = $reel->issues()->sum('net_consumed_weight');
    $calculatedBalance = $reel->original_weight - $totalConsumed;
    $difference = abs($calculatedBalance - $reel->balance_weight);
    
    if ($difference > 0.01) {
        \Log::warning('Balance weight discrepancy detected', [
            'reel_no' => $reel->reel_no,
            'stored_balance' => $reel->balance_weight,
            'calculated_balance' => $calculatedBalance,
            'difference' => $difference,
            'action' => 'auto_correcting'
        ]);
        
        $reel->balance_weight = max($calculatedBalance, 0);
        return false;
    }
    
    return true;
}
```

**Integration:** This method is called before saving the reel in:
- `ReelIssueController::store()`
- `ReelIssueController::update()`
- `ReelIssueController::destroy()`
- `ReelReturnController::store()`
- `ReelReturnController::update()`
- `ReelReturnController::destroy()`

---

### 3. DAILY RECONCILIATION - Artisan Command

**File:** `app/Console/Commands/ReconcileReelBalances.php`

**Purpose:** Automated daily reconciliation to catch and fix any discrepancies

**Features:**
- ✅ Scans all reels for balance discrepancies
- ✅ Compares stored balance with calculated balance from transactions
- ✅ Provides detailed report of discrepancies
- ✅ Auto-fixes discrepancies (with --auto-fix flag)
- ✅ Comprehensive logging
- ✅ Database transaction safety

**Usage:**

```bash
# Generate report only (no changes)
php artisan reels:reconcile --report-only

# Interactive mode (asks for confirmation before fixing)
php artisan reels:reconcile

# Auto-fix mode (automatically corrects all discrepancies)
php artisan reels:reconcile --auto-fix
```

**Example Output:**
```
Starting Reel Balance Reconciliation...
Timestamp: 2026-01-22 18:00:00

Found 4 reel(s) with discrepancies:

+----------+-------------+-----------------+-----------------+------------+----------------+----------------+
| Reel No  | Original    | Current Balance | Correct Balance | Difference | Current Status | Correct Status |
+----------+-------------+-----------------+-----------------+------------+----------------+----------------+
| RL111616 | 822.00 kg   | 550.00 kg       | 822.00 kg       | 272.00 kg  | partially_used | in_stock       |
| RL111620 | 775.00 kg   | 150.00 kg       | 775.00 kg       | 625.00 kg  | partially_used | in_stock       |
| RL111626 | 875.00 kg   | 0.00 kg         | 400.00 kg       | 400.00 kg  | fully_used     | partially_used |
| RL111627 | 868.00 kg   | 0.00 kg         | 550.00 kg       | 550.00 kg  | fully_used     | partially_used |
+----------+-------------+-----------------+-----------------+------------+----------------+----------------+

Do you want to fix these discrepancies? (yes/no)
```

---

### 4. AUTOMATED SCHEDULING - Task Scheduler

**File:** `app/Console/Kernel.php`

**Schedules:**
1. **Daily Auto-Reconciliation:** Every day at 2:00 AM
   ```php
   $schedule->command('reels:reconcile --auto-fix')
       ->dailyAt('02:00')
       ->appendOutputTo(storage_path('logs/reel-reconciliation.log'));
   ```

2. **Weekly Report:** Every Sunday at 11:00 PM
   ```php
   $schedule->command('reels:reconcile --report-only')
       ->weeklyOn(0, '23:00')
       ->appendOutputTo(storage_path('logs/reel-reconciliation-weekly.log'))
       ->emailOutputTo('admin@example.com');
   ```

**Setup Laravel Scheduler (Windows):**

1. **Using Task Scheduler:**
   - Open Task Scheduler
   - Create Basic Task
   - Name: "ReelStock Laravel Scheduler"
   - Trigger: Daily at startup
   - Action: Start a program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `c:\xampp\htdocs\ReelStock\artisan schedule:run`
   - Repeat: Every 1 minute for 24 hours

2. **Using start-app.bat (Recommended for testing):**
   Add to your start-app.bat:
   ```batch
   start "" php artisan schedule:work
   ```

---

## LOGGING & MONITORING

### Log Files

1. **Real-time Validation Logs:**
   - Location: `storage/logs/laravel.log`
   - Contains: All auto-corrections from controllers
   - Filter: Search for "Balance weight discrepancy detected"

2. **Daily Reconciliation Logs:**
   - Location: `storage/logs/reel-reconciliation.log`
   - Contains: Daily reconciliation results
   - Rotates: Append mode

3. **Weekly Report Logs:**
   - Location: `storage/logs/reel-reconciliation-weekly.log`
   - Contains: Weekly summary reports

### Monitoring Commands

```bash
# Check today's reconciliation log
tail -n 100 storage/logs/reel-reconciliation.log

# Search for discrepancies in Laravel log
grep "Balance weight discrepancy" storage/logs/laravel.log

# Run immediate reconciliation check
php artisan reels:reconcile --report-only
```

---

## MANUAL CORRECTION WORKFLOW

If you discover a discrepancy and need to fix it manually:

### Step 1: Investigate
```bash
# Run the SQL diagnostic script
mysql -u root -p reelstock < database/scripts/fix_reel_balances_20260122.sql
```

### Step 2: Review
- Review transaction history for the affected reels
- Verify issue dates, quantities, and returns
- Check for missing or incorrect transactions

### Step 3: Fix
Option A - Use Artisan command (Recommended):
```bash
php artisan reels:reconcile
```

Option B - Use SQL script:
- Uncomment Step 4 in `fix_reel_balances_20260122.sql`
- Run the script again

### Step 4: Verify
```bash
# Run verification query
php artisan reels:reconcile --report-only
```

---

## PREVENTION MEASURES

The following measures now prevent future discrepancies:

1. ✅ **Real-time Validation:** Every transaction validates and syncs balance
2. ✅ **Auto-Correction:** Discrepancies are automatically fixed inline
3. ✅ **Daily Reconciliation:** Scheduled job catches any edge cases
4. ✅ **Comprehensive Logging:** All corrections are logged for audit
5. ✅ **Weekly Reports:** Regular monitoring reports
6. ✅ **Transaction Safety:** All operations use database transactions

---

## API BEHAVIOR CHANGES

### Controllers Now Auto-Correct

When a discrepancy is detected during an issue/return operation:

**Before:**
- Transaction completes with incorrect balance
- Discrepancy accumulates silently

**Now:**
- Discrepancy is detected
- Warning is logged
- Balance is auto-corrected
- Transaction completes with correct balance
- User sees no error (seamless correction)

**Example Log Entry:**
```json
{
  "level": "warning",
  "message": "Balance weight discrepancy detected",
  "context": {
    "reel_no": "RL111616",
    "stored_balance": 550,
    "calculated_balance": 822,
    "difference": 272,
    "action": "auto_correcting"
  }
}
```

---

## TESTING RECOMMENDATIONS

### Test Scenarios

1. **Issue a Reel:**
   - Create an issue
   - Verify balance updates correctly
   - Check logs for any discrepancies

2. **Return to Stock:**
   - Return a reel to stock
   - Verify status changes correctly
   - Check balance is synchronized

3. **Return to Supplier:**
   - Return a reel to supplier
   - Verify balance decreases
   - Check logs

4. **Edit/Delete Transactions:**
   - Edit an existing issue
   - Delete a return
   - Verify balances recalculate correctly

5. **Run Reconciliation:**
   ```bash
   php artisan reels:reconcile --report-only
   ```
   - Should show no discrepancies

---

## TROUBLESHOOTING

### Issue: Reconciliation command not found

**Solution:**
```bash
php artisan clear-compiled
composer dump-autoload
php artisan config:clear
```

### Issue: Scheduler not running

**Solution (Windows):**
1. Check Task Scheduler has the task
2. Verify PHP path is correct
3. Test manually: `php artisan schedule:run`
4. Use `php artisan schedule:work` for testing

### Issue: Logs not writing

**Solution:**
```bash
# Ensure storage directory is writable
chmod -R 755 storage
# Or on Windows, right-click storage folder → Properties → Security → Edit
```

### Issue: Discrepancies still appearing

**Solution:**
1. Review the logs: `storage/logs/laravel.log`
2. Run manual reconciliation: `php artisan reels:reconcile`
3. Check for concurrent transactions (race conditions)
4. Ensure all controllers are using the updated code

---

## MAINTENANCE

### Daily Tasks
- No manual intervention required (automated)

### Weekly Tasks
- Review `reel-reconciliation-weekly.log`
- Check for recurring discrepancies
- Investigate root causes if discrepancies are frequent

### Monthly Tasks
- Verify scheduler is running correctly
- Review log file sizes and rotate if needed
- Test reconciliation command manually

---

## FUTURE ENHANCEMENTS

Consider implementing:
1. **Database Triggers:** MySQL triggers to enforce balance consistency at DB level
2. **Real-time Alerts:** Slack/Email notifications for discrepancies
3. **Dashboard Widget:** Display reconciliation status on dashboard
4. **Audit Trail UI:** Web interface to view correction history
5. **Batch Correction:** Web UI for manual batch corrections
6. **Performance Optimization:** Caching of balance calculations

---

## CONTACT & SUPPORT

For issues or questions about this system:
1. Check this documentation first
2. Review log files
3. Run diagnostic commands
4. Contact system administrator

---

**Document Version:** 1.0  
**Last Updated:** 2026-01-22  
**Status:** ✅ IMPLEMENTED AND ACTIVE
