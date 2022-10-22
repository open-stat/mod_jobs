CREATE TABLE `mod_jobs_categories` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `title` varchar(255) NOT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb3;

CREATE TABLE `mod_jobs_currency` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `abbreviation` varchar(5) NOT NULL,
    `rate` decimal(8,4) DEFAULT NULL,
    `scale` decimal(8,2) DEFAULT NULL,
    `date_rate` date NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `mod_jobs_employers` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(500) NOT NULL,
    `unp` varchar(100) DEFAULT NULL,
    `region` varchar(255) DEFAULT NULL,
    `url` varchar(500) NOT NULL,
    `description` text,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb3;

CREATE TABLE `mod_jobs_employers_vacancies` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `employer_id` int unsigned NOT NULL,
    `source_id` int unsigned NOT NULL,
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
    CONSTRAINT `fk1_mod_jobs_employers_vacancies` FOREIGN KEY (`employer_id`) REFERENCES `mod_jobs_employers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_jobs_employers_vacancies` FOREIGN KEY (`source_id`) REFERENCES `mod_jobs_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb3;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb3;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb3;

CREATE TABLE `mod_jobs_employers_vacancies_salary` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `vacancy_id` int unsigned NOT NULL,
    `salary_min_byn` decimal(11,2) unsigned NOT NULL,
    `salary_max_byn` decimal(11,2) unsigned NOT NULL,
    `date_salary` date NOT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `vacancy_id` (`vacancy_id`),
    CONSTRAINT `fk1_mod_jobs_employers_vacancies_activity` FOREIGN KEY (`vacancy_id`) REFERENCES `mod_jobs_employers_vacancies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb3;

CREATE TABLE `mod_jobs_employers_vacancies_summary` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `source_id` int unsigned NOT NULL,
    `category_id` int unsigned NOT NULL,
    `date_summary` date NOT NULL,
    `total_count` int unsigned DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `source_id` (`source_id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `fk1_mod_jobs_employers_vacancies_summary` FOREIGN KEY (`source_id`) REFERENCES `mod_jobs_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_jobs_employers_vacancies_summary` FOREIGN KEY (`category_id`) REFERENCES `mod_jobs_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb3;

CREATE TABLE `mod_jobs_pages` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `source_name` varchar(100) NOT NULL,
    `type` varchar(100) NOT NULL,
    `url` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    `status` enum('pending','process','complete','error') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'pending',
    `content` longblob NOT NULL,
    `options` json DEFAULT NULL,
    `note` text,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `status` (`status`),
    KEY `type` (`type`),
    KEY `source_name` (`source_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb3;

CREATE TABLE `mod_jobs_professions` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `title` varchar(255) NOT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb3;

CREATE TABLE `mod_jobs_resume` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `source_id` int unsigned NOT NULL,
    `employer_id` int unsigned DEFAULT NULL,
    `category_id` int unsigned NOT NULL,
    `status_load` enum('pending','process','complete') NOT NULL DEFAULT 'pending',
    `status_parse` enum('pending','process','complete') NOT NULL DEFAULT 'pending',
    `title` varchar(500) NOT NULL,
    `salary_min_byn` decimal(11,2) unsigned DEFAULT NULL,
    `salary_max_byn` decimal(11,2) unsigned DEFAULT NULL,
    `salary_min` decimal(11,2) unsigned DEFAULT NULL,
    `salary_max` decimal(11,2) unsigned DEFAULT NULL,
    `currency` varchar(255) DEFAULT NULL,
    `url` varchar(500) NOT NULL,
    `tags` varchar(500) DEFAULT NULL,
    `region` varchar(255) DEFAULT NULL,
    `lat` varchar(100) DEFAULT NULL,
    `lng` varchar(100) DEFAULT NULL,
    `date_publish` date DEFAULT NULL,
    `date_close` date DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `source_id` (`source_id`),
    KEY `category_id` (`category_id`),
    KEY `employer_id` (`employer_id`),
    CONSTRAINT `fk1_mod_jobs_resume` FOREIGN KEY (`source_id`) REFERENCES `mod_jobs_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_jobs_resume` FOREIGN KEY (`category_id`) REFERENCES `mod_jobs_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk3_mod_jobs_resume` FOREIGN KEY (`employer_id`) REFERENCES `mod_jobs_employers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb3;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb3;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb3;

CREATE TABLE `mod_jobs_resume_salary` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `resume_id` int unsigned NOT NULL,
    `salary_byn` decimal(8,2) unsigned NOT NULL,
    `date_salary` date NOT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `resume_id` (`resume_id`),
    CONSTRAINT `fk1_mod_jobs_resume_activity` FOREIGN KEY (`resume_id`) REFERENCES `mod_jobs_resume` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb3;

CREATE TABLE `mod_jobs_resume_summary` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `source_id` int unsigned NOT NULL,
    `category_id` int unsigned NOT NULL,
    `date_summary` date NOT NULL,
    `total_passive` int unsigned DEFAULT NULL,
    `total_active` int unsigned DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `source_id` (`source_id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `fk1_mod_jobs_resume_summary` FOREIGN KEY (`source_id`) REFERENCES `mod_jobs_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_jobs_resume_summary` FOREIGN KEY (`category_id`) REFERENCES `mod_jobs_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb3;

CREATE TABLE `mod_jobs_sources` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `title` varchar(255) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb3;

CREATE TABLE `mod_jobs_summary` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `category_id` int unsigned NOT NULL,
    `date_summary` date NOT NULL,
    `total_vacancies` int unsigned DEFAULT NULL,
    `total_resume` int unsigned DEFAULT NULL,
    `vacancies_salary_min` decimal(11,2) unsigned DEFAULT NULL,
    `vacancies_salary_max` decimal(11,2) unsigned DEFAULT NULL,
    `resume_salary_min` decimal(11,2) unsigned DEFAULT NULL,
    `resume_salary_max` decimal(11,2) unsigned DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `fk1_mod_jobs_summary` FOREIGN KEY (`category_id`) REFERENCES `mod_jobs_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb3;