CREATE TABLE `tables` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `owner_id` INT DEFAULT NULL,
    `slug` VARCHAR(190) NOT NULL,
    `title` VARCHAR(190) NOT NULL,
    `lang` VARCHAR(190) DEFAULT NULL,
    `created` DATETIME NOT NULL,
    `modified` DATETIME DEFAULT NULL,
    UNIQUE INDEX UNIQ_F6298F46989D9B62 (`slug`),
    INDEX IDX_F6298F467E3C61F9 (`owner_id`),
    INDEX idx_table_slug (`slug`),
    PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE `table_element` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `table_id` INT NOT NULL,
    `code` VARCHAR(190) NOT NULL,
    `label` LONGTEXT NOT NULL,
    INDEX IDX_43731F36ECFF285C (`table_id`),
    INDEX idx_table_label (`table_id`, `label`(190)),
    UNIQUE INDEX idx_table_code (`table_id`, `code`),
    PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE `tables` ADD CONSTRAINT FK_F6298F467E3C61F9 FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;
ALTER TABLE `table_element` ADD CONSTRAINT FK_43731F36ECFF285C FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE CASCADE;
