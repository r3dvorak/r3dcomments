SET @r3dcomments_ip_hash_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME LIKE '%\\_r3dcomments' ESCAPE '\\'
      AND COLUMN_NAME = 'ip_hash'
);

SET @r3dcomments_ip_hash_sql = IF(
    @r3dcomments_ip_hash_exists > 0,
    'SELECT 1',
    'ALTER TABLE `#__r3dcomments` ADD COLUMN `ip_hash` CHAR(64) NULL AFTER `ip`'
);

PREPARE r3dcomments_stmt FROM @r3dcomments_ip_hash_sql;
EXECUTE r3dcomments_stmt;
DEALLOCATE PREPARE r3dcomments_stmt;
