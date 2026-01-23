SELECT 
    r.id,
    r.reel_no,
    r.original_weight,
    r.balance_weight,
    r.status,
    r.created_at,
    r.updated_at
FROM reels r
WHERE r.reel_no = 'RL111470';

-- Check returns for this reel
SELECT 
    rr.id,
    rr.return_date,
    rr.returned_to,
    rr.remaining_weight,
    rr.condition,
    rr.remarks,
    rr.created_at
FROM reel_returns rr
JOIN reels r ON rr.reel_id = r.id
WHERE r.reel_no = 'RL111470'
ORDER BY rr.return_date DESC, rr.created_at DESC;

-- Check issues for this reel
SELECT 
    ri.id,
    ri.issue_date,
    ri.quantity_issued,
    ri.return_to_stock_weight,
    ri.net_consumed_weight,
    ri.issued_to,
    ri.created_at
FROM reel_issues ri
JOIN reels r ON ri.reel_id = r.id
WHERE r.reel_no = 'RL111470'
ORDER BY ri.issue_date DESC, ri.created_at DESC;
