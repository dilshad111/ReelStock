-- Fix Script for Reel #111470
-- This reel was returned to supplier but status was not updated

-- Check current state
SELECT 
    r.id,
    r.reel_no,
    r.original_weight,
    r.balance_weight,
    r.status,
    rr.return_date,
    rr.returned_to,
    rr.remaining_weight
FROM reels r
LEFT JOIN reel_returns rr ON rr.reel_id = r.id
WHERE r.reel_no = 'RL111470';

-- Update the reel status and balance
UPDATE reels
SET 
    status = 'returned_to_supplier',
    balance_weight = 0,
    updated_at = NOW()
WHERE reel_no = 'RL111470';

-- Verify the fix
SELECT 
    r.id,
    r.reel_no,
    r.original_weight,
    r.balance_weight,
    r.status,
    r.updated_at
FROM reels r
WHERE r.reel_no = 'RL111470';
