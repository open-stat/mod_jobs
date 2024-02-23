CREATE TABLE `mod_jobs_sources` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `title` varchar(255) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_jobs_summary` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `source_id` int(11) unsigned not null,
    `date_summary` datetime NOT NULL,
    `total_vacancies` int unsigned DEFAULT NULL,
    `total_resume` int unsigned DEFAULT NULL,
    `total_week_invites` int unsigned DEFAULT NULL,
    `total_employers` int unsigned DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `source_id` (`source_id`),
    CONSTRAINT `fk1_mod_jobs_summary` FOREIGN KEY (`source_id`) REFERENCES `mod_jobs_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_jobs_regions` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `country` varchar(255) NOT NULL,
    `area` varchar(255) NOT NULL,
    `city` varchar(255) DEFAULT NULL,
    `lng` varchar(255) DEFAULT NULL,
    `lat` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_jobs_categories` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `title` varchar(255) NOT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_jobs_categories_summary` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `source_id` int(11) unsigned not null,
    `category_id` int unsigned NOT NULL,
    `date_summary` date NOT NULL,
    `total_vacancies` int unsigned DEFAULT NULL,
    `total_resume` int unsigned DEFAULT NULL,
    `total_people` int unsigned DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `source_id` (`source_id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `fk1_mod_jobs_categories_summary` FOREIGN KEY (`source_id`) REFERENCES `mod_jobs_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_jobs_categories_summary` FOREIGN KEY (`category_id`) REFERENCES `mod_jobs_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_jobs_professions` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `title` varchar(255) NOT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_jobs_professions_summary` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `source_id` int(11) unsigned not null,
    `profession_id` int unsigned NOT NULL,
    `date_summary` date NOT NULL,
    `total_vacancies` int unsigned DEFAULT NULL,
    `total_resume` int unsigned DEFAULT NULL,
    `total_people` int unsigned DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `source_id` (`source_id`),
    KEY `profession_id` (`profession_id`),
    CONSTRAINT `fk1_mod_jobs_professions_summary` FOREIGN KEY (`source_id`) REFERENCES `mod_jobs_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_jobs_professions_summary` FOREIGN KEY (`profession_id`) REFERENCES `mod_jobs_professions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_jobs_currency` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `abbreviation` varchar(5) NOT NULL,
    `rate` decimal(8,4) DEFAULT NULL,
    `scale` decimal(8,2) DEFAULT NULL,
    `date_rate` date NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_jobs_employers` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(500) NOT NULL,
    `unp` varchar(100) DEFAULT NULL,
    `region` varchar(255) DEFAULT NULL,
    `url` varchar(500) DEFAULT NULL,
    `description` text,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `title` (`title`),
    KEY `url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_jobs_employers_vacancies` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `employer_id` int unsigned NOT NULL,
    `source_id` int unsigned NOT NULL,
    `region_id` int unsigned DEFAULT NULL,
    `status_load` enum('pending','process','complete') NOT NULL DEFAULT 'pending',
    `status_parse` enum('pending','process','complete') NOT NULL DEFAULT 'pending',
    `title` varchar(255) NOT NULL,
    `region` varchar(255) DEFAULT NULL,
    `url` varchar(500) NOT NULL,
    `tags` varchar(500) DEFAULT NULL,
    `salary_min_byn` decimal(11,2) unsigned DEFAULT NULL,
    `salary_max_byn` decimal(11,2) unsigned DEFAULT NULL,
    `salary_min` decimal(11,2) unsigned DEFAULT NULL,
    `salary_max` decimal(11,2) unsigned DEFAULT NULL,
    `currency` varchar(255) DEFAULT NULL,
    `address` varchar(255) DEFAULT NULL,
    `lat` varchar(100) DEFAULT NULL,
    `lng` varchar(100) DEFAULT NULL,
    `description` text,
    `description_full` text,
    `date_publish` date DEFAULT NULL,
    `date_close` date DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `employer_id` (`employer_id`),
    KEY `source_id` (`source_id`),
    KEY `url` (`url`),
    KEY `region_id` (`region_id`),
    KEY `title` (`title`),
    CONSTRAINT `fk1_mod_jobs_employers_vacancies` FOREIGN KEY (`employer_id`) REFERENCES `mod_jobs_employers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_jobs_employers_vacancies` FOREIGN KEY (`source_id`) REFERENCES `mod_jobs_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk3_mod_jobs_employers_vacancies` FOREIGN KEY (`region_id`) REFERENCES `mod_jobs_regions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_jobs_employers_vacancies_activity` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `vacancy_id` int unsigned NOT NULL,
    `salary_min_byn` decimal(11,2) unsigned DEFAULT NULL,
    `salary_max_byn` decimal(11,2) unsigned DEFAULT NULL,
    `date_activity` date NOT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `vacancy_id` (`vacancy_id`),
    KEY `date_activity` (`date_activity`),
    CONSTRAINT `fk1_mod_jobs_employers_vacancies_activity` FOREIGN KEY (`vacancy_id`) REFERENCES `mod_jobs_employers_vacancies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_jobs_employers_vacancies_categories` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `vacancy_id` int unsigned NOT NULL,
    `category_id` int unsigned NOT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `vacancy_id` (`vacancy_id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `fk1_mod_jobs_employers_vacancies_categories` FOREIGN KEY (`vacancy_id`) REFERENCES `mod_jobs_employers_vacancies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_jobs_employers_vacancies_categories` FOREIGN KEY (`category_id`) REFERENCES `mod_jobs_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_jobs_employers_vacancies_professions` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `vacancy_id` int unsigned NOT NULL,
    `profession_id` int unsigned NOT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `vacancy_id` (`vacancy_id`),
    KEY `profession_id` (`profession_id`),
    CONSTRAINT `fk1_mod_jobs_employers_vacancies_professions` FOREIGN KEY (`vacancy_id`) REFERENCES `mod_jobs_employers_vacancies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_jobs_employers_vacancies_professions` FOREIGN KEY (`profession_id`) REFERENCES `mod_jobs_professions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_jobs_resume` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `source_id` int unsigned NOT NULL,
    `last_employer_id` int unsigned DEFAULT NULL,
    `status_load` enum('pending','process','complete') NOT NULL DEFAULT 'pending',
    `status_parse` enum('pending','process','complete') NOT NULL DEFAULT 'pending',
    `title` varchar(500) NOT NULL,
    `age` int unsigned DEFAULT NULL,
    `salary_byn` decimal(11,2) unsigned DEFAULT NULL,
    `salary` decimal(11,2) unsigned DEFAULT NULL,
    `currency` varchar(255) DEFAULT NULL,
    `url` varchar(500) NOT NULL,
    `last_profession` varchar(500) DEFAULT NULL,
    `search_status` enum('passive','active') DEFAULT NULL,
    `tags` varchar(500) DEFAULT NULL,
    `region` varchar(255) DEFAULT NULL,
    `experience_year` int unsigned DEFAULT '0',
    `experience_month` int unsigned DEFAULT '0',
    `lat` varchar(100) DEFAULT NULL,
    `lng` varchar(100) DEFAULT NULL,
    `date_last_up` datetime DEFAULT NULL,
    `date_publish` date DEFAULT NULL,
    `date_close` date DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `source_id` (`source_id`),
    KEY `last_employer_id` (`last_employer_id`),
    KEY `title` (`title`),
    KEY `url` (`url`),
    CONSTRAINT `fk1_mod_jobs_resume` FOREIGN KEY (`source_id`) REFERENCES `mod_jobs_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk3_mod_jobs_resume` FOREIGN KEY (`last_employer_id`) REFERENCES `mod_jobs_employers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_jobs_resume_activity` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `resume_id` int unsigned NOT NULL,
    `salary_byn` decimal(8,2) unsigned DEFAULT NULL,
    `date_activity` date NOT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `resume_id` (`resume_id`),
    KEY `date_activity` (`date_activity`),
    CONSTRAINT `fk1_mod_jobs_resume_activity` FOREIGN KEY (`resume_id`) REFERENCES `mod_jobs_resume` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_jobs_resume_categories` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `resume_id` int unsigned NOT NULL,
    `category_id` int unsigned NOT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `resume_id` (`resume_id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `fk1_mod_jobs_resume_categories` FOREIGN KEY (`resume_id`) REFERENCES `mod_jobs_resume` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_jobs_resume_categories` FOREIGN KEY (`category_id`) REFERENCES `mod_jobs_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_jobs_resume_professions` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `resume_id` int unsigned NOT NULL,
    `profession_id` int unsigned NOT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `resume_id` (`resume_id`),
    KEY `profession_id` (`profession_id`),
    CONSTRAINT `fk1_mod_jobs_resume_professions` FOREIGN KEY (`resume_id`) REFERENCES `mod_jobs_resume` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_jobs_resume_professions` FOREIGN KEY (`profession_id`) REFERENCES `mod_jobs_professions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `mod_jobs_pages` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `source_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `url` varchar(1500) COLLATE utf8mb4_general_ci NOT NULL,
    `file_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `file_size` int DEFAULT NULL,
    `status` enum('pending','process','complete','error') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
    `options` json DEFAULT NULL,
    `note` blob,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `status` (`status`),
    KEY `type` (`type`),
    KEY `source_name` (`source_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
