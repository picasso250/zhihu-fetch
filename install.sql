CREATE TABLE `zhihu`.`to_be` (
  `id` INT NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `create_time` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`));
ALTER TABLE `zhihu`.`to_be` 
ADD UNIQUE INDEX `idx_url` (`url` ASC);
ALTER TABLE `zhihu`.`to_be` 
ADD COLUMN `fetched` TINYINT UNSIGNED NOT NULL AFTER `create_time`;
ALTER TABLE `zhihu`.`to_be` 
CHANGE COLUMN `fetched` `fetched` TINYINT(3) UNSIGNED NOT NULL AFTER `url`;
ALTER TABLE `zhihu`.`to_be` 
CHANGE COLUMN `id` `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ;
