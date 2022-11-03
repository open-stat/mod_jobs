<?php
use Core2\Mod\Jobs;
use Symfony\Component\DomCrawler\Crawler;

require_once DOC_ROOT . 'core2/inc/classes/Common.php';
require_once "classes/autoload.php";


/**
 * @property ModJobsController $modJobs
 */
class ModJobsCli extends Common {

    /**
     * Получение списка вакансий по категориям
     * @throws \Exception
     */
    public function loadVacanciesCategories() {

        $model   = new Jobs\Index\Model();
        $sources = $model->getSources();

        if ( ! empty($sources)) {
            foreach ($sources as $source_name => $source_class) {
                if ($source_class instanceof Jobs\Index\Source) {
                    $categories = $source_class->getCategories();

                    foreach ($categories as $category_name => $category) {
                        if (empty($category['title'])) {
                            continue;
                        }

                        try {
                            $pages = $source_class->loadVacanciesCategory($category_name);

                            if ( ! empty($pages)) {
                                foreach ($pages as $page) {
                                    if (empty($page['url']) || empty($page['content'])) {
                                        continue;
                                    }

                                    $this->modJobs->dataJobsPages->addPage($source_name, 'vacancies_categories', [
                                        'url'     => $page['url'],
                                        'content' => $page['content'],
                                        'options' => [
                                            'category_name'  => $category_name,
                                            'category_title' => $category['title'],
                                        ],
                                    ]);
                                }
                            }

                        } catch (\Exception $e) {
                            echo $e->getMessage() . PHP_EOL;
                        }
                    }
                }
            }
        }
    }


    /**
     * Получение списка вакансий по профессиям
     * @throws \Exception
     */
    public function loadVacanciesProfessions() {

        $model   = new Jobs\Index\Model();
        $sources = $model->getSources();

        if ( ! empty($sources)) {
            foreach ($sources as $source_name => $source_class) {
                if ($source_class instanceof Jobs\Index\Source) {
                    $professions = $source_class->getProfessions();

                    foreach ($professions as $profession_name => $profession) {
                        if (empty($profession['title'])) {
                            continue;
                        }

                        try {
                            $pages = $source_class->loadVacanciesProfessions($profession_name);

                            if ( ! empty($pages)) {
                                foreach ($pages as $page) {

                                    if (empty($page['url']) || empty($page['content'])) {
                                        continue;
                                    }

                                    $this->modJobs->dataJobsPages->addPage($source_name, 'vacancies_professions', [
                                        'url'     => $page['url'],
                                        'content' => $page['content'],
                                        'options' => [
                                            'profession_name'  => $profession_name,
                                            'profession_title' => $profession['title']
                                        ],
                                    ]);
                                }
                            }

                        } catch (\Exception $e) {
                            echo $e->getMessage() . PHP_EOL;
                        }
                    }
                }
            }
        }
    }


    /**
     * Получение полных данных по вакансиям
     * @throws \Exception
     */
    public function loadVacancies() {

        $model   = new Jobs\Index\Model();
        $sources = $model->getSources();

        if ( ! empty($sources)) {
            foreach ($sources as $source_name => $source_class) {
                if ($source_class instanceof Jobs\Index\Source) {

                    $this->modJobs->dataJobsEmployersVacancies->resetStatusLoadProcess();
                    $source       = $this->modJobs->dataJobsSources->getRowByName($source_name, $source_class->getTitle());
                    $vacancy_rows = $this->modJobs->dataJobsEmployersVacancies->getRowsByStatusLoad($source->id, 'pending');

                    foreach ($vacancy_rows as $vacancy_row) {
                        $vacancy_row->status_load = 'process';
                        $vacancy_row->save();

                        $page = $source_class->loadResume($vacancy_row->url);

                        if (empty($page['url']) || empty($page['content'])) {
                            continue;
                        }

                        $this->modJobs->dataJobsPages->addPage($source_name, 'vacancy', [
                            'url'     => $page['url'],
                            'content' => $page['content'],
                            'options' => ['resume_id', $vacancy_row->id],
                        ]);

                        $vacancy_row->status_load = 'complete';
                        $vacancy_row->save();
                    }
                }
            }
        }
    }


    /**
     * Получение списка резюме по категориям
     * @throws \Exception
     */
    public function loadResumeCategories() {

        $model   = new Jobs\Index\Model();
        $sources = $model->getSources();

        if ( ! empty($sources)) {
            foreach ($sources as $source_name => $source_class) {
                if ($source_class instanceof Jobs\Index\Source) {

                    $categories = $source_class->getCategories();

                    foreach ($categories as $category_name => $category) {
                        if (empty($category['title'])) {
                            continue;
                        }

                        // Активно ищет работу
                        try {
                            $pages = $source_class->loadResumeCategory($category_name, ['search_status' => 'active']);

                            if ( ! empty($pages)) {
                                foreach ($pages as $page) {
                                    if (empty($page['url']) || empty($page['content'])) {
                                        continue;
                                    }

                                    $this->modJobs->dataJobsPages->addPage($source_name, 'resume_categories', [
                                        'url'     => $page['url'],
                                        'content' => $page['content'],
                                        'options' => [
                                            'category_name'  => $category_name,
                                            'category_title' => $category['title'],
                                            'search_status'  => 'active',
                                        ],
                                    ]);
                                }
                            }

                        } catch (\Exception $e) {
                            echo $e->getMessage() . PHP_EOL;
                        }


                        // Рассматривает предложения
                        try {
                            $pages = $source_class->loadResumeCategory($category_name, ['search_status' => 'passive']);

                            if ( ! empty($pages)) {
                                foreach ($pages as $page) {
                                    if (empty($page['url']) || empty($page['content'])) {
                                        continue;
                                    }

                                    $this->modJobs->dataJobsPages->addPage($source_name, 'resume_categories', [
                                        'url'     => $page['url'],
                                        'content' => $page['content'],
                                        'options' => [
                                            'category_name'  => $category_name,
                                            'category_title' => $category['title'],
                                            'search_status'  => 'passive',
                                        ],
                                    ]);
                                }
                            }

                        } catch (\Exception $e) {
                            echo $e->getMessage() . PHP_EOL;
                        }
                    }
                }
            }
        }
    }


    /**
     * Получение списка резюме по профессиям
     * @throws \Exception
     */
    public function loadResumeProfessions() {

        $model   = new Jobs\Index\Model();
        $sources = $model->getSources();

        if ( ! empty($sources)) {
            foreach ($sources as $source_name => $source_class) {
                if ($source_class instanceof Jobs\Index\Source) {
                    $professions = $source_class->getProfessions();

                    foreach ($professions as $profession_name => $profession) {
                        if (empty($profession['title'])) {
                            continue;
                        }

                        // Активно ищет работу
                        try {
                            $pages = $source_class->loadResumeProfessions($profession_name, ['search_status' => 'active']);

                            if ( ! empty($pages)) {
                                foreach ($pages as $page) {
                                    if (empty($page['url']) || empty($page['content'])) {
                                        continue;
                                    }

                                    $this->modJobs->dataJobsPages->addPage($source_name, 'resume_professions', [
                                        'url'     => $page['url'],
                                        'content' => $page['content'],
                                        'options' => [
                                            'profession_name'  => $profession_name,
                                            'profession_title' => $profession['title'],
                                            'search_status'    => 'active',
                                        ],
                                    ]);
                                }
                            }

                        } catch (\Exception $e) {
                            echo $e->getMessage() . PHP_EOL;
                        }


                        // Рассматривает предложения
                        try {
                            $pages = $source_class->loadResumeProfessions($profession_name, ['search_status' => 'passive']);

                            if ( ! empty($pages)) {
                                foreach ($pages as $page) {
                                    if (empty($page['url']) || empty($page['content'])) {
                                        continue;
                                    }

                                    $this->modJobs->dataJobsPages->addPage($source_name, 'resume_professions', [
                                        'url'     => $page['url'],
                                        'content' => $page['content'],
                                        'options' => [
                                            'profession_name'  => $profession_name,
                                            'profession_title' => $profession['title'],
                                            'search_status'    => 'passive',
                                        ],
                                    ]);
                                }
                            }

                        } catch (\Exception $e) {
                            echo $e->getMessage() . PHP_EOL;
                        }
                    }
                }
            }
        }
    }


    /**
     * Получение полных данных по резюме
     * @throws \Exception
     */
    public function loadResume() {

        $model   = new Jobs\Index\Model();
        $sources = $model->getSources();

        if ( ! empty($sources)) {
            foreach ($sources as $source_name => $source_class) {
                if ($source_class instanceof Jobs\Index\Source) {

                    $this->modJobs->dataJobsResume->resetStatusLoadProcess();
                    $source      = $this->modJobs->dataJobsSources->getRowByName($source_name, $source_class->getTitle());
                    $resume_rows = $this->modJobs->dataJobsResume->getRowsByStatusLoad($source->id, 'pending');

                    foreach ($resume_rows as $resume_row) {
                        $resume_row->status_load = 'process';
                        $resume_row->save();

                        $page = $source_class->loadResume($resume_row->url);

                        if (empty($page['url']) || empty($page['content'])) {
                            continue;
                        }

                        $this->modJobs->dataJobsPages->addPage($source_name, 'resume', [
                            'url'     => $page['url'],
                            'content' => $page['content'],
                            'options' => ['resume_id', $resume_row->id],
                        ]);

                        $resume_row->status_load = 'complete';
                        $resume_row->save();
                    }
                }
            }
        }
    }


    /**
     * Обработка информации
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function parseSources() {

        $model   = new Jobs\Index\Model();
        $sources = $model->getSources();

        if ( ! empty($sources)) {
            foreach ($sources as $source_name => $source_class) {
                if ($source_class instanceof Jobs\Index\Source) {

                    $this->modJobs->dataJobsPages->resetStatusProcess();
                    $source         = $this->modJobs->dataJobsSources->getRowByName($source_name, $source_class->getTitle());
                    $page_vacancies = $this->modJobs->dataJobsPages->getRowsByTypeStatus($source_name, ['vacancies_categories', 'vacancies_professions'], 'pending');

                    foreach ($page_vacancies as $page) {
                        $page->status = 'process';
                        $page->save();

                        $error_messages = [];

                        try {
                            $page_content = gzuncompress($page->content);
                            $page_options = $page->options ? json_decode($page->options, true) : [];
                            $page_options['date_created'] = $page->date_created;

                            $parse_page = $source_class->parseVacanciesList($page_content);

                            if ( ! empty($parse_page['vacancies'])) {
                                foreach ($parse_page['vacancies'] as $vacancy) {
                                    $this->db->beginTransaction();
                                    try {
                                        $model->saveVacancy((int)$source->id, $vacancy, $page_options);
                                        $this->db->commit();
                                    } catch (\Exception $e) {
                                        $this->db->rollback();
                                        $error_messages[] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
                                    }
                                }
                            }

                            $model->saveSummary($parse_page, $page_options);

                            $page->status = 'complete';
                            $page->note   = implode(PHP_EOL.PHP_EOL, $error_messages);
                            $page->save();


                        } catch (\Exception $e) {
                            $page->status = 'error';
                            $page->note   = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
                            $page->save();
                        }
                    }

                    $pages_resume = $this->modJobs->dataJobsPages->getRowsByTypeStatus($source_name, ['resume_categories', 'resume_professions'], 'pending');

                    foreach ($pages_resume as $page) {
                        $page->status = 'process';
                        $page->save();

                        $error_messages = [];

                        try {
                            $page_content = gzuncompress($page->content);
                            $page_options = $page->options ? json_decode($page->options, true) : [];
                            $page_options['date_created'] = $page->date_created;

                            $parse_page = $source_class->parseResumeList($page_content, $page_options);


                            if ( ! empty($parse_page['resume'])) {
                                foreach ($parse_page['resume'] as $resume) {
                                    $this->db->beginTransaction();
                                    try {
                                        $model->saveResume((int)$source->id, $resume, $page_options);
                                        $this->db->commit();
                                    } catch (\Exception $e) {
                                        $this->db->rollback();
                                        $error_messages[] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
                                    }
                                }
                            }


                            $model->saveSummary($parse_page, $page_options);

                            $page->status = 'complete';
                            $page->note   = implode(PHP_EOL.PHP_EOL, $error_messages);
                            $page->save();


                        } catch (\Exception $e) {
                            $page->status = 'error';
                            $page->note   = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
                            $page->save();
                        }
                    }
                }
            }
        }
    }


    /**
     * Закрытие неактивных вакансий и резюме
     * @return void
     * @throws Zend_Db_Table_Exception
     */
    public function closedVacanciesResume(): void {

        // Вакансии
        $vacancies = $this->db->fetchAll("
            SELECT jev.id,
                   (SELECT jeva.date_activity
                    FROM mod_jobs_employers_vacancies_activity AS jeva 
                    WHERE jev.id = jeva.vacancy_id
                    ORDER BY jeva.date_activity DESC
                    LIMIT 1) AS date_last_activity
            
            FROM mod_jobs_employers_vacancies AS jev
            WHERE jev.date_close IS NULL
              AND (SELECT jeva.date_activity
                   FROM mod_jobs_employers_vacancies_activity AS jeva 
                   WHERE jev.id = jeva.vacancy_id
                   ORDER BY jeva.date_activity DESC
                   LIMIT 1) <= DATE_SUB(NOW(), INTERVAL 5 DAY)
        ");


        foreach ($vacancies as $vacancy) {

            $vacancy_row = $this->modJobs->dataJobsEmployersVacancies->find($vacancy['id'])->current();
            $vacancy_row->date_close = $vacancy['date_last_activity'];
            $vacancy_row->save();
        }


        // Резюме
        $resume_items = $this->db->fetchAll("
            SELECT jr.id,
                   (SELECT jra.date_activity
                    FROM mod_jobs_resume_activity AS jra 
                    WHERE jr.id = jra.resume_id
                    ORDER BY jra.date_activity DESC
                    LIMIT 1) AS date_last_activity
            
            FROM mod_jobs_resume AS jr
            WHERE jr.date_close IS NULL
              AND (SELECT jra.date_activity
                   FROM mod_jobs_resume_activity AS jra 
                   WHERE jr.id = jra.resume_id
                   ORDER BY jra.date_activity DESC
                   LIMIT 1) <= DATE_SUB(NOW(), INTERVAL 5 DAY)
        ");


        foreach ($resume_items as $resume) {

            $resume_row = $this->modJobs->dataJobsResume->find($resume['id'])->current();
            $resume_row->date_close = $resume['date_last_activity'];
            $resume_row->save();
        }
    }
}
