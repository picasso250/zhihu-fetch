CREATE TABLE `zhihu`.`to_be` (
  `id` INT NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `create_time` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`));
ALTER TABLE `zhihu`.`to_be` 
ADD UNIQUE INDEX `idx_url` (`url` ASC);
ALTER TABLE `zhihu`.`to_be` 
ADD COLUMN `fetched` TINYINT UNSIGNED NOT NULL AFTER `create_time`;
