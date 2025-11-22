-- Migration: Stored Procedure to Cleanup User Login Logs (Keep last 30 days)
-- Date: 2024
-- Description: Creates a stored procedure to automatically delete login logs older than 30 days

DELIMITER //

CREATE PROCEDURE IF NOT EXISTS cleanup_user_login_logs()
BEGIN
    DECLARE deleted_count INT DEFAULT 0;
    
    -- Delete records older than 30 days
    DELETE FROM user_login_logs
    WHERE logged_in_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Get the number of deleted rows
    SET deleted_count = ROW_COUNT();
    
    -- Return the count of deleted records (optional, for logging/monitoring)
    SELECT deleted_count AS deleted_records;
END //

DELIMITER ;

-- Grant execute permissions (adjust user as needed)
-- GRANT EXECUTE ON PROCEDURE cleanup_user_login_logs TO 'your_app_user'@'localhost';

