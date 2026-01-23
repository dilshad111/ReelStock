-- =====================================================
-- REEL BALANCE CORRECTION SCRIPT
-- Date: 2026-01-22
-- Purpose: Fix balance_weight discrepancies for reels
-- =====================================================

-- Step 1: Check current state of problem reels
SELECT 
    r.reel_no,
    r.original_weight,
    r.balance_weight AS current_balance,
    r.status,
    r.updated_at,
    -- Calculate balance from transactions
    (r.original_weight - COALESCE(SUM(ri.net_consumed_weight), 0)) AS calculated_balance
FROM reels r
LEFT JOIN reel_issues ri ON r.id = ri.reel_id
WHERE r.reel_no IN ('RL111616', 'RL111620', 'RL111626', 'RL111627')
GROUP BY r.id, r.reel_no, r.original_weight, r.balance_weight, r.status, r.updated_at;

-- Step 2: Detailed transaction history for these reels
SELECT 
    r.reel_no,
    'Issue' AS transaction_type,
    ri.issue_date AS transaction_date,
    ri.quantity_issued,
    ri.return_to_stock_weight,
    ri.net_consumed_weight,
    ri.created_at
FROM reels r
JOIN reel_issues ri ON r.id = ri.reel_id
WHERE r.reel_no IN ('RL111616', 'RL111620', 'RL111626', 'RL111627')

UNION ALL

SELECT 
    r.reel_no,
    CONCAT('Return to ', rr.returned_to) AS transaction_type,
    rr.return_date AS transaction_date,
    NULL AS quantity_issued,
    NULL AS return_to_stock_weight,
    rr.remaining_weight AS net_consumed_weight,
    rr.created_at
FROM reels r
JOIN reel_returns rr ON r.id = rr.reel_id
WHERE r.reel_no IN ('RL111616', 'RL111620', 'RL111626', 'RL111627')

ORDER BY reel_no, transaction_date, created_at;

-- Step 3: Calculate correct balance_weight for each reel
WITH reel_calculations AS (
    SELECT 
        r.id,
        r.reel_no,
        r.original_weight,
        r.balance_weight AS current_balance,
        r.status AS current_status,
        -- Calculate correct balance from transaction history
        (r.original_weight - COALESCE(SUM(ri.net_consumed_weight), 0)) AS correct_balance,
        -- Determine correct status
        CASE 
            WHEN (r.original_weight - COALESCE(SUM(ri.net_consumed_weight), 0)) <= 0 THEN 'fully_used'
            WHEN (r.original_weight - COALESCE(SUM(ri.net_consumed_weight), 0)) < r.original_weight THEN 'partially_used'
            ELSE 'in_stock'
        END AS correct_status
    FROM reels r
    LEFT JOIN reel_issues ri ON r.id = ri.reel_id
    WHERE r.reel_no IN ('RL111616', 'RL111620', 'RL111626', 'RL111627')
    GROUP BY r.id, r.reel_no, r.original_weight, r.balance_weight, r.status
)
SELECT 
    reel_no,
    original_weight,
    current_balance,
    correct_balance,
    (correct_balance - current_balance) AS difference,
    current_status,
    correct_status,
    CASE 
        WHEN current_balance != correct_balance OR current_status != correct_status THEN 'NEEDS FIX'
        ELSE 'OK'
    END AS status
FROM reel_calculations;

-- Step 4: Apply corrections (UNCOMMENT TO EXECUTE)
-- WARNING: This will modify the database. Review the above output first!
/*
UPDATE reels r
LEFT JOIN (
    SELECT 
        r.id,
        -- Calculate correct balance from transaction history
        (r.original_weight - COALESCE(SUM(ri.net_consumed_weight), 0)) AS correct_balance,
        -- Determine correct status
        CASE 
            WHEN (r.original_weight - COALESCE(SUM(ri.net_consumed_weight), 0)) <= 0 THEN 'fully_used'
            WHEN (r.original_weight - COALESCE(SUM(ri.net_consumed_weight), 0)) < r.original_weight THEN 'partially_used'
            ELSE 'in_stock'
        END AS correct_status
    FROM reels r
    LEFT JOIN reel_issues ri ON r.id = ri.reel_id
    WHERE r.reel_no IN ('RL111616', 'RL111620', 'RL111626', 'RL111627')
    GROUP BY r.id, r.original_weight
) calc ON r.id = calc.id
SET 
    r.balance_weight = calc.correct_balance,
    r.status = calc.correct_status
WHERE r.reel_no IN ('RL111616', 'RL111620', 'RL111626', 'RL111627')
  AND calc.id IS NOT NULL;
*/

-- Step 5: Verification after fix (run after uncommenting Step 4)
/*
SELECT 
    r.reel_no,
    r.original_weight,
    r.balance_weight AS fixed_balance,
    r.status AS fixed_status,
    (r.original_weight - COALESCE(SUM(ri.net_consumed_weight), 0)) AS verified_balance
FROM reels r
LEFT JOIN reel_issues ri ON r.id = ri.reel_id
WHERE r.reel_no IN ('RL111616', 'RL111620', 'RL111626', 'RL111627')
GROUP BY r.id, r.reel_no, r.original_weight, r.balance_weight, r.status;
*/
