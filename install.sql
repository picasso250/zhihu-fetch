CREATE TABLE `task` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL,
  `fetched` tinyint(3) unsigned NOT NULL,
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_url` (`url`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
CREATE TABLE `question` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `detail` text,
  `create_time` datetime NOT NULL ,
  `fetch` tinyint(4) NOT NULL DEFAULT '0',
  `follow_count` int(10) unsigned NOT NULL,
  `answer_count` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_title` (`title`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
CREATE TABLE `answer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qid` int(10) unsigned DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `create_time` datetime NOT NULL ,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_qu` (`qid`,`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
