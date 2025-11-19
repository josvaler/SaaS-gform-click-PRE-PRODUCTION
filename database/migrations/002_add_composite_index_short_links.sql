-- Migration: Add Composite Index for Better Query Performance
-- Date: 2025
-- Purpose: Optimize queries that filter by user_id, created_at, and is_active together

-- Add composite index for user_id + created_at + is_active
-- This improves performance for explore.php queries with filters
ALTER TABLE short_links 
ADD INDEX idx_user_created_active (user_id, created_at, is_active);

