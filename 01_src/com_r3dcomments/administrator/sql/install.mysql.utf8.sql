CREATE TABLE IF NOT EXISTS `#__r3dcomments` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,

    `created_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `state` TINYINT(3) NOT NULL DEFAULT 1,
    `ordering` INT(11) NOT NULL DEFAULT 0,

    `parent_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `context` VARCHAR(100) NOT NULL DEFAULT 'com_content.article',
    `item_id` INT(11) UNSIGNED NOT NULL,

    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `author_name` VARCHAR(150) DEFAULT NULL,
    `author_email` VARCHAR(150) DEFAULT NULL,

    `comment` MEDIUMTEXT NOT NULL,

    `quoted_comment_id` INT(11) UNSIGNED DEFAULT NULL,
    `moderation_token` VARCHAR(100) DEFAULT NULL,

    `fields` MEDIUMTEXT DEFAULT NULL,
    `params_reserved_by_joomla` MEDIUMTEXT DEFAULT NULL,
    `ip` VARCHAR(45) DEFAULT NULL,
    `ip_hash` CHAR(64) DEFAULT NULL,

    `created` DATETIME NOT NULL,
    `modified_by` INT(11) UNSIGNED DEFAULT NULL,
    `modified` DATETIME DEFAULT NULL,

    PRIMARY KEY (`id`)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__r3dcomments_subscriptions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `context` varchar(255) NOT NULL,
    `item_id` int(11) NOT NULL,
    `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_user_context_item` (`user_id`,`context`,`item_id`)
) 
ENGINE=InnoDB 
DEFAULT CHARSET=utf8mb4 
COLLATE=utf8mb4_unicode_ci;

-- Update params for com_r3dcomments to set default email body if it's empty
UPDATE `#__extensions`
SET `params` = JSON_SET(`params`, '$.email_body', 'A new comment has been posted on the website.\n\n--------------------------------------------------\nAuthor: {author_name}\nE-Mail: {author_email}\nComment:\n{comment}\n--------------------------------------------------\n\nActions:\nPublish: {publish_url}\nTrash: {trash_url}\n\nEdit in backend:\n{backend_url}')
WHERE `element` = 'com_r3dcomments' AND `type` = 'component'
  AND (JSON_UNQUOTE(JSON_EXTRACT(`params`, '$.email_body')) IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(`params`, '$.email_body')) = '');

