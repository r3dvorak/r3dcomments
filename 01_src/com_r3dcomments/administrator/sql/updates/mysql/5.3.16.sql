ALTER TABLE `#__r3dcomments`
    ADD COLUMN IF NOT EXISTS `ip_hash` CHAR(64) NULL AFTER `ip`;
