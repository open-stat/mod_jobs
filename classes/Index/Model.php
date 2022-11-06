<?php
namespace Core2\Mod\Jobs\Index;

use GuzzleHttp\Exception\GuzzleException;

require_once DOC_ROOT . 'core2/inc/classes/Common.php';


/**
 * @property \ModJobsController $modJobs
 */
class Model extends \Common {


    /**
     * @return array
     * @throws \Zend_Config_Exception
     * @throws \Exception
     */
    public function getSources(): array {

        $config  = $this->getModuleConfig('jobs');
        $sources = [];

        $sources_config = $config->sources
            ? $config->sources->toArray()
            : [];

        foreach ($sources_config as $source_name) {
            if ( ! is_string($source_name)) {
                continue;
            }

            $sources[$source_name] = $this->getSource($source_name);
        }

        return $sources;
    }


    /**
     * @param string $source_name
     * @return Source
     * @throws \Zend_Config_Exception
     */
    public function getSource(string $source_name): Source {

        $config = $this->getModuleConfig('jobs');

        $file_name = __DIR__ . "/Sources/{$source_name}.php";

        if ( ! file_exists($file_name)) {
            throw new \Exception(sprintf('Файл с классом источника не найден: %s', $file_name));
        }

        require_once $file_name;

        $source_class = __NAMESPACE__ . "\\Sources\\{$source_name}";

        if ( ! class_exists($source_class)) {
            throw new \Exception(sprintf('Класс источника не найден: %s', $source_class));
        }

        $options = [
            'debug' => $config?->debug
        ];


        return new $source_class($options);
    }


    /**
     * @param int   $source_id
     * @param array $vacancy
     * @param array $options
     * @return int
     * @throws GuzzleException
     * @throws \Exception
     */
    public function saveVacancy(int $source_id, array $vacancy, array $options = []): int {

        if (empty($vacancy['url']) ||
            empty($vacancy['title']) ||
            empty($vacancy['employer_title']) ||
            empty($options['date_created'])
        ) {
            return 0;
        }

        $date_created = new \DateTime($options['date_created']);

        // Если пришла старая закрытая вакансия
        $vacancy_close = $this->modJobs->dataJobsEmployersVacancies->getRowBySourceUrlDate($source_id, $vacancy['url'], $date_created);
        if ( ! empty($vacancy_close)) {
            return 0;
        }


        $salary_min_byn = null;
        $salary_max_byn = null;

        if ( ! empty($vacancy['currency'])) {
            if ( ! empty($vacancy['salary_min'])) {
                $salary_min_byn = $this->modJobs->getConvertCurrency($vacancy['salary_min'], $vacancy['currency'], $date_created);
            }

            if ( ! empty($vacancy['salary_max'])) {
                $salary_max_byn = $this->modJobs->getConvertCurrency($vacancy['salary_max'], $vacancy['currency'], $date_created);
            }
        }


        $employer = $this->modJobs->dataJobsEmployers->getRowByTitle($vacancy['employer_title']);

        if (empty($employer)) {
            $employer = $this->modJobs->dataJobsEmployers->createRow([
                'title'       => $vacancy['employer_title'],
                'unp'         => $vacancy['employer_unp'] ?? null,
                'region'      => $vacancy['employer_region'] ?? null,
                'url'         => $vacancy['employer_url'] ?? null,
                'description' => $vacancy['employer_description'] ?? null,
            ]);
            $employer->save();
        }


        $vacancy_row = $this->modJobs->dataJobsEmployersVacancies->getRowBySourceUrl($source_id, $vacancy['url']);

        if (empty($vacancy_row) || ! empty($vacancy_row->date_close)) {
            $vacancy_row = $this->modJobs->dataJobsEmployersVacancies->createRow([
                'employer_id'      => $employer->id,
                'source_id'        => $source_id,
                'status_load'      => 'pending',
                'status_parse'     => 'pending',
                'title'            => $vacancy['title'],
                'region'           => $vacancy['region'] ?? null,
                'url'              => $vacancy['url'],
                'salary_min_byn'   => $salary_min_byn,
                'salary_max_byn'   => $salary_max_byn,
                'salary_min'       => $vacancy['salary_min'] ?? null,
                'salary_max'       => $vacancy['salary_max'] ?? null,
                'currency'         => $vacancy['currency'] ?? null,
                'description'      => $vacancy['description'] ?? null,
                'date_publish'     => $date_created->format('Y-m-d'),
                'date_close'       => null,
            ]);
            $vacancy_row->save();

        } else {
            if ($vacancy_row->title       != $vacancy['title'] ?? null ||
                $vacancy_row->region      != $vacancy['region'] ?? null ||
                $vacancy_row->salary_min  != $vacancy['salary_min'] ?? null ||
                $vacancy_row->salary_max  != $vacancy['salary_max'] ?? null ||
                $vacancy_row->currency    != $vacancy['currency'] ?? null ||
                $vacancy_row->salary_min  != $vacancy['salary_min'] ?? null ||
                $vacancy_row->description != $vacancy['description'] ?? null
            ) {
                $date_close = clone $date_created;
                $date_close->modify('-1 day');

                $vacancy_row->date_close = $date_close->format('Y-m-d');
                $vacancy_row->save();

                $this->modJobs->dataJobsEmployersVacanciesActivity->addDate($vacancy_row->id, $date_created, [
                    'salary_min_byn' => $vacancy_row->salary_min_byn,
                    'salary_max_byn' => $vacancy_row->salary_max_byn,
                ]);


                $vacancy_row = $this->modJobs->dataJobsEmployersVacancies->createRow([
                    'employer_id'    => $employer->id,
                    'source_id'      => $source_id,
                    'status_load'    => 'pending',
                    'status_parse'   => 'pending',
                    'title'          => $vacancy['title'],
                    'region'         => $vacancy['region'] ?? null,
                    'url'            => $vacancy['url'],
                    'salary_min_byn' => $salary_min_byn,
                    'salary_max_byn' => $salary_max_byn,
                    'salary_min'     => $vacancy['salary_min'] ?? null,
                    'salary_max'     => $vacancy['salary_max'] ?? null,
                    'currency'       => $vacancy['currency'] ?? null,
                    'description'    => $vacancy['description'] ?? null,
                    'date_publish'   => $date_created->format('Y-m-d'),
                    'date_close'     => null,
                ]);

            } else {
                $vacancy_row->salary_min_byn = $salary_min_byn;
                $vacancy_row->salary_max_byn = $salary_max_byn;
            }

            $vacancy_row->save();
        }


        $this->modJobs->dataJobsEmployersVacanciesActivity->addDate($vacancy_row->id, $date_created, [
            'salary_min_byn' => $salary_min_byn,
            'salary_max_byn' => $salary_max_byn,
        ]);


        // Категории
        if ( ! empty($options['category_name']) &&
             ! empty($options['category_title'])
        ) {
            $category = $this->modJobs->dataJobsCategories->getRowByCategoryName($options['category_name'], $options['category_title']);

            if ( ! empty($category)) {
                $vacancy_category = $this->modJobs->dataJobsEmployersVacanciesCategories->getRowByVacancyCategoryId($vacancy_row->id, $category->id);

                if (empty($vacancy_category)) {
                    $vacancy_category = $this->modJobs->dataJobsEmployersVacanciesCategories->createRow([
                        'vacancy_id'  => $vacancy_row->id,
                        'category_id' => $category->id,
                    ]);
                    $vacancy_category->save();
                }
            }
        }


        // Профессии
        if ( ! empty($options['profession_name']) &&
             ! empty($options['profession_title'])
        ) {
            $profession = $this->modJobs->dataJobsProfessions->getRowByProfessionName($options['profession_name'], $options['profession_title']);

            if ( ! empty($profession)) {
                $vacancy_profession = $this->modJobs->dataJobsEmployersVacanciesProfessions->getRowByVacancyProfessionId($vacancy_row->id, $profession->id);

                if (empty($vacancy_profession)) {
                    $vacancy_profession = $this->modJobs->dataJobsEmployersVacanciesProfessions->createRow([
                        'vacancy_id'    => $vacancy_row->id,
                        'profession_id' => $profession->id,
                    ]);
                    $vacancy_profession->save();
                }
            }
        }

        return (int)$vacancy_row->id;
    }


    /**
     * @param int   $source_id
     * @param array $resume
     * @param array $options
     * @return int
     * @throws GuzzleException
     * @throws \Exception
     */
    public function saveResume(int $source_id, array $resume, array $options = []): int {

        if (empty($resume['url']) ||
            empty($resume['title']) ||
            empty($options['date_created'])
        ) {
            return 0;
        }

        $date_created = new \DateTime($options['date_created']);

        // Если пришла старое закрытое резюме
        $resume_close = $this->modJobs->dataJobsResume->getRowBySourceUrlDate($source_id, $resume['url'], $date_created);
        if ( ! empty($resume_close)) {
            return 0;
        }


        $salary_byn = null;

        if ( ! empty($resume['currency'])) {

            if ( ! empty($resume['salary'])) {
                $salary_byn = $this->modJobs->getConvertCurrency($resume['salary'], $resume['currency'], $date_created);
            }
        }

        $last_employer = null;

        if ( ! empty($resume['last_employer_title'])) {
            $last_employer = $this->modJobs->dataJobsEmployers->getRowByTitle($resume['last_employer_title']);

;
            if (empty($last_employer)) {
                $last_employer = $this->modJobs->dataJobsEmployers->createRow([
                    'title' => $resume['last_employer_title'],
                ]);
                $last_employer->save();
            }
        }

        $resume_row = $this->modJobs->dataJobsResume->getRowBySourceUrl($source_id, $resume['url']);
        $currency   = $resume['currency'] ?: ($resume['currency_origin'] ?: null);

        if (empty($resume_row) || ! empty($resume_row->date_close)) {
            $resume_row = $this->modJobs->dataJobsResume->createRow([
                'source_id'        => $source_id,
                'last_employer_id' => $last_employer->id ?? null,
                'last_profession'  => $resume['last_profession'] ?? null,
                'title'            => $resume['title'],
                'url'              => $resume['url'],
                'age'              => $resume['age'] ?? null,
                'experience_year'  => $resume['experience_year'] ?? null,
                'experience_month' => $resume['experience_month'] ?? null,
                'salary_byn'       => $salary_byn,
                'salary'           => $resume['salary'] ?? null,
                'currency'         => $currency,
                'date_last_up'     => $resume['date_last_update'] ?? null,
                'search_status'    => $options['search_status'] ?? null,
                'date_publish'     => $date_created->format('Y-m-d'),
                'date_close'       => null,
            ]);
            $resume_row->save();

        } else {
            if ($resume_row->title            != $resume['title'] ?? null ||
                $resume_row->last_employer_id != $last_employer->id ?? null ||
                $resume_row->salary           != $resume['salary'] ?? null ||
                $resume_row->search_status    != $options['search_status'] ?? null ||
                $resume_row->tags             != ! empty($resume['labels']) ? json_encode($resume['labels']) : null ||
                $resume_row->currency         != $currency
            ) {
                $date_close = clone $date_created;
                $date_close->modify('-1 day');

                $resume_row->date_close = $date_close->format('Y-m-d');
                $resume_row->save();

                $this->modJobs->dataJobsResumeActivity->addDate($resume_row->id, $date_created, [
                    'salary_byn' => $resume_row->salary_byn,
                ]);


                $resume_row = $this->modJobs->dataJobsResume->createRow([
                    'source_id'        => $source_id,
                    'last_employer_id' => $last_employer->id ?? null,
                    'last_profession'  => $resume['last_profession'] ?? null,
                    'title'            => $resume['title'],
                    'url'              => $resume['url'],
                    'age'              => $resume['age'] ?? null,
                    'experience_year'  => $resume['experience_year'] ?? null,
                    'experience_month' => $resume['experience_month'] ?? null,
                    'tags'             => ! empty($resume['labels']) ? json_encode($resume['labels']) : null,
                    'salary_byn'       => $salary_byn,
                    'salary'           => $resume['salary'] ?? null,
                    'currency'         => $currency,
                    'date_last_up'     => $resume['date_last_update'] ?? null,
                    'search_status'    => $options['search_status'] ?? null,
                    'date_publish'     => $date_created->format('Y-m-d'),
                    'date_close'       => null,
                ]);

            } else {
                $resume_row->experience_year  = $resume['experience_year'] ?? null;
                $resume_row->experience_month = $resume['experience_month'] ?? null;
                $resume_row->salary_byn       = $salary_byn;
                $resume_row->date_last_up     = $resume['date_last_update'] ?? null;
            }

            $resume_row->save();
        }


        $this->modJobs->dataJobsResumeActivity->addDate($resume_row->id, $date_created, [
            'salary_byn' => $salary_byn,
        ]);


        // Категории
        if ( ! empty($options['category_name']) &&
             ! empty($options['category_title'])
        ) {
            $category = $this->modJobs->dataJobsCategories->getRowByCategoryName($options['category_name'], $options['category_title']);

            if ( ! empty($category)) {
                $vacancy_category = $this->modJobs->dataJobsResumeCategories->getRowByResumeCategoryId($resume_row->id, $category->id);


                if (empty($vacancy_category)) {
                    $vacancy_category = $this->modJobs->dataJobsResumeCategories->createRow([
                        'resume_id'   => $resume_row->id,
                        'category_id' => $category->id,
                    ]);
                    $vacancy_category->save();
                }
            }
        }


        // Профессии
        if ( ! empty($options['profession_name']) &&
             ! empty($options['profession_title'])
        ) {
            $profession = $this->modJobs->dataJobsProfessions->getRowByProfessionName($options['profession_name'], $options['profession_title']);


            if ( ! empty($profession)) {
                $vacancy_profession = $this->modJobs->dataJobsResumeProfessions->getRowByResumeProfessionId($resume_row->id, $profession->id);


                if (empty($vacancy_profession)) {
                    $vacancy_profession = $this->modJobs->dataJobsResumeProfessions->createRow([
                        'resume_id'     => $resume_row->id,
                        'profession_id' => $profession->id,
                    ]);
                    $vacancy_profession->save();
                }
            }
        }

        return (int)$resume_row->id;
    }


    /**
     * @param array $parse_page
     * @param array $page_options
     * @return void
     * @throws \Exception
     */
    public function saveSummary(array $parse_page, array $page_options): void {

        if (empty($page_options['date_created'])) {
            return;
        }

        $date_created = new \DateTime($page_options['date_created']);

        // Профессии общее
        if ( ! empty($page_options['profession_name']) &&
             ! empty($page_options['profession_title']) &&

            ( ! empty($parse_page['vacancies_found']) ||
              ! empty($parse_page['resume_found']) ||
              ! empty($parse_page['people_found']))
        ) {
            $profession         = $this->modJobs->dataJobsProfessions->getRowByProfessionName($page_options['profession_name'], $page_options['profession_title']);
            $profession_summary = $this->modJobs->dataJobsProfessionsSummary->getRowByProfessionIdDate($profession->id, $date_created);

            if (empty($profession_summary)) {
                $profession_summary = $this->modJobs->dataJobsProfessionsSummary->createRow([
                    'profession_id'   => $profession->id,
                    'date_summary'    => $date_created->format('Y-m-d'),
                    'total_vacancies' => $parse_page['vacancies_found'] ?? null,
                    'total_resume'    => $parse_page['resume_found'] ?? null,
                    'total_people'    => $parse_page['people_found'] ?? null,
                ]);
                $profession_summary->save();

            } else {
                if ( ! empty($parse_page['vacancies_found'])) {
                    $profession_summary->total_vacancies = $parse_page['vacancies_found'];
                }
                if ( ! empty($parse_page['resume_found'])) {
                    $profession_summary->total_resume = $parse_page['resume_found'];
                }
                if ( ! empty($parse_page['people_found'])) {
                    $profession_summary->total_people = $parse_page['people_found'];
                }

                $profession_summary->save();
            }
        }


        // Категории общее
        if ( ! empty($page_options['category_name']) &&
             ! empty($page_options['category_title']) &&

            ( ! empty($parse_page['vacancies_found']) ||
              ! empty($parse_page['resume_found']) ||
              ! empty($parse_page['people_found']))
        ) {
            $category         = $this->modJobs->dataJobsCategories->getRowByCategoryName($page_options['category_name'], $page_options['category_title']);
            $category_summary = $this->modJobs->dataJobsCategoriesSummary->getRowByCategoryIdDate($category->id, $date_created);

            if (empty($category_summary)) {
                $category_summary = $this->modJobs->dataJobsCategoriesSummary->createRow([
                    'category_id'     => $category->id,
                    'date_summary'    => $date_created->format('Y-m-d'),
                    'total_vacancies' => $parse_page['vacancies_found'] ?? null,
                    'total_resume'    => $parse_page['resume_found'] ?? null,
                    'total_people'    => $parse_page['people_found'] ?? null,
                ]);
                $category_summary->save();

            } else {
                if ( ! empty($parse_page['vacancies_found'])) {
                    $category_summary->total_vacancies = $parse_page['vacancies_found'];
                }
                if ( ! empty($parse_page['resume_found'])) {
                    $category_summary->total_resume = $parse_page['resume_found'];
                }
                if ( ! empty($parse_page['people_found'])) {
                    $category_summary->total_people = $parse_page['people_found'];
                }

                $category_summary->save();
            }
        }


        // Итоговые показатели
        if ( ! empty($parse_page['total_vacancies']) ||
             ! empty($parse_page['total_resume']) ||
             ! empty($parse_page['total_employers']) ||
             ! empty($parse_page['total_week_invites'])
        ) {
            $summary = $this->modJobs->dataJobsSummary->getRowByDate($date_created);

            if (empty($summary)) {
                $summary = $this->modJobs->dataJobsSummary->createRow([
                    'date_summary'       => $date_created->format('Y-m-d H:i:s'),
                    'total_vacancies'    => $parse_page['total_vacancies'] ?? null,
                    'total_resume'       => $parse_page['total_resume'] ?? null,
                    'total_employers'    => $parse_page['total_employers'] ?? null,
                    'total_week_invites' => $parse_page['total_week_invites'] ?? null,
                ]);
                $summary->save();

            } elseif ($summary->date_summary < $date_created->format('Y-m-d H:i:s')) {

                $summary->date_summary = $date_created->format('Y-m-d H:i:s');

                if ( ! empty($parse_page['total_vacancies'])) {
                    $summary->total_vacancies = $parse_page['total_vacancies'];
                }
                if ( ! empty($parse_page['total_resume'])) {
                    $summary->total_resume = $parse_page['total_resume'];
                }
                if ( ! empty($parse_page['total_employers'])) {
                    $summary->total_employers = $parse_page['total_employers'];
                }
                if ( ! empty($parse_page['total_week_invites'])) {
                    $summary->total_week_invites = $parse_page['total_week_invites'];
                }

                $summary->save();
            }
        }
    }
}