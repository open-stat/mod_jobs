<?php
use Core2\Mod\Jobs;

require_once DOC_ROOT . 'core2/inc/classes/Common.php';
require_once DOC_ROOT . 'core2/inc/classes/Parallel.php';
require_once "classes/autoload.php";


/**
 * @property ModJobsController $modJobs
 * @property ModMetricsApi     $apiMetrics
 */
class ModJobsCli extends Common {

    /**
     * Получение списка вакансий по категориям
     * @throws \Exception
     */
    public function loadVacanciesCategories(): void {

        $model    = new Jobs\Index\Model();
        $sources  = $model->getSources();
        $is_error = false;

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


                                    $this->apiMetrics->incPrometheus('core2_jobs_vacancies_load', 1, [
                                        'labels'   => ['host' => parse_url($page['url'], PHP_URL_HOST) ?? '-', 'type' => 'categories'],
                                        'job'      => 'core2',
                                        'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                                    ]);
                                }
                            }

                        } catch (\Exception $e) {
                            echo $e->getMessage() . PHP_EOL;
                            $is_error = true;
                        }
                    }
                }
            }
        }

        if ($is_error) {
            throw new \Exception('Во время получения вакансий произошли ошибки');
        }
    }


    /**
     * Получение списка вакансий по профессиям
     * @throws \Exception
     */
    public function loadVacanciesProfessions(): void {

        $model    = new Jobs\Index\Model();
        $sources  = $model->getSources();
        $is_error = false;

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

                                    $this->apiMetrics->incPrometheus('core2_jobs_vacancies_load', 1, [
                                        'labels'   => ['host' => parse_url($page['url'], PHP_URL_HOST) ?? '-', 'type' => 'professions'],
                                        'job'      => 'core2',
                                        'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                                    ]);
                                }
                            }

                        } catch (\Exception $e) {
                            echo $e->getMessage() . PHP_EOL;
                            $is_error = true;
                        }
                    }
                }
            }
        }

        if ($is_error) {
            throw new \Exception('Во время получения вакансий произошли ошибки');
        }
    }


    /**
     * Получение полных данных по вакансиям
     * @throws \Exception
     */
    public function loadVacancies(): void {

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

                        $page = $source_class->loadVacancies($vacancy_row->url);

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


                        $this->apiMetrics->incPrometheus('core2_jobs_vacancy_load', 1, [
                            'labels'   => ['source' => $source_name],
                            'job'      => 'core2',
                            'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                        ]);
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

        $model    = new Jobs\Index\Model();
        $sources  = $model->getSources();
        $is_error = false;

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

                                    $this->apiMetrics->incPrometheus('core2_jobs_resume_list_load', 1, [
                                        'labels'   => ['host' => parse_url($page['url'], PHP_URL_HOST) ?? '-', 'type' => 'professions'],
                                        'job'      => 'core2',
                                        'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                                    ]);
                                }
                            }

                        } catch (\Exception $e) {
                            echo $e->getMessage() . PHP_EOL;
                            $is_error = true;
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

                                    $this->apiMetrics->incPrometheus('core2_jobs_resume_list_load', 1, [
                                        'labels'   => ['host' => parse_url($page['url'], PHP_URL_HOST) ?? '-', 'type' => 'categories'],
                                        'job'      => 'core2',
                                        'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                                    ]);
                                }
                            }

                        } catch (\Exception $e) {
                            echo $e->getMessage() . PHP_EOL;
                            $is_error = true;
                        }
                    }
                }
            }
        }

        if ($is_error) {
            throw new \Exception('Во время получения резюме произошли ошибки');
        }
    }


    /**
     * Получение списка резюме по профессиям
     * @throws \Exception
     */
    public function loadResumeProfessions() {

        $model    = new Jobs\Index\Model();
        $sources  = $model->getSources();
        $is_error = false;

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


                                    $this->apiMetrics->incPrometheus('core2_jobs_resume_list_load', 1, [
                                        'labels'   => ['host' => parse_url($page['url'], PHP_URL_HOST) ?? '-', 'type' => 'professions'],
                                        'job'      => 'core2',
                                        'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                                    ]);
                                }
                            }

                        } catch (\Exception $e) {
                            echo $e->getMessage() . PHP_EOL;
                            $is_error = true;
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

                                    $this->apiMetrics->incPrometheus('core2_jobs_resume_list_load', 1, [
                                        'labels'   => ['host' => parse_url($page['url'], PHP_URL_HOST) ?? '-', 'type' => 'professions'],
                                        'job'      => 'core2',
                                        'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                                    ]);
                                }
                            }

                        } catch (\Exception $e) {
                            echo $e->getMessage() . PHP_EOL;
                            $is_error = true;
                        }
                    }
                }
            }
        }

        if ($is_error) {
            throw new \Exception('Во время получения резюме произошли ошибки');
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


                        $this->apiMetrics->incPrometheus('core2_jobs_resume_load', 1, [
                            'labels'   => ['source' => $source_name],
                            'job'      => 'core2',
                            'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                        ]);
                    }
                }
            }
        }
    }


    /**
     * Загрузка курсов валют
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws Exception
     */
    public function loadCurrency() {

        $date_rate = new \DateTime('2022-01-01');
        $date_end  = date('Y-m-d');

        while ($date_rate->format('Y-m-d') <= $date_end) {

            $currency = $this->modJobs->dataJobsCurrency->fetchRow(
                $this->modJobs->dataJobsCurrency->select()
                    ->where('date_rate = ?', $date_rate->format('Y-m-d'))
                    ->limit(1)
            );

            if ( ! $currency) {
                try {
                    $nbrb_currencies = (new Jobs\Index\NbrbApi())->getCurrency($date_rate);

                    if (empty($nbrb_currencies)) {
                        throw new \Exception('Не удалось получить курсы валют');
                    }

                    foreach ($nbrb_currencies as $nbrb_currency) {
                        $currency_date = $this->modJobs->dataJobsCurrency->getRowByCurrencyDate($nbrb_currency['abbreviation'], $date_rate);

                        if (empty($currency_date)) {
                            $currency_date = $this->modJobs->dataJobsCurrency->createRow([
                                'abbreviation' => $nbrb_currency['abbreviation'],
                                'rate'         => $nbrb_currency['rate'],
                                'scale'        => $nbrb_currency['scale'],
                                'date_rate'    => $date_rate->format("Y-m-d")
                            ]);
                            $currency_date->save();
                        }
                    }

                    echo $date_rate->format('Y-m-d') . PHP_EOL;


                } catch (\Exception $e) {
                    echo $e->getMessage() . ' - ' . $date_rate->format('Y-m-d') . PHP_EOL;
                }
                sleep(1);
            }


            $date_rate->modify('+1 day');
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
        $config  = $this->getModuleConfig('jobs');

        if ( ! empty($sources)) {
            foreach ($sources as $source_name => $source_class) {
                if ($source_class instanceof Jobs\Index\Source) {

                    $this->modJobs->dataJobsPages->resetStatusProcess();
                    $source         = $this->modJobs->dataJobsSources->getRowByName($source_name, $source_class->getTitle());
                    $page_vacancies = $this->modJobs->dataJobsPages->getRowsByTypeStatus($source_name, ['vacancies_categories', 'vacancies_professions'], 'pending');

                    $parallel = new \Core2\Parallel([ 'pool_size' => $config?->pool_size ?: 4 ]);

                    foreach ($page_vacancies as $page) {

                        $parallel->addTask(function () use ($source, $page) {
                            $model = new Jobs\Index\Model();

                            $page->status = 'process';
                            $page->save();

                            $error_messages = [];

                            try {
                                $date         = new \DateTime($page->date_created);
                                $file_content = $model->getSourceFile('jobs', $date, $page->file_name);

                                $page_content = gzuncompress(base64_decode($file_content['content']));
                                $page_options = $page->options ? json_decode($page->options, true) : [];
                                $page_options['date_created'] = $page->date_created;

                                $source_class = $model->getSource($source->name);
                                $parse_page   = $source_class->parseVacanciesList($page_content);

                                if ( ! empty($parse_page['vacancies'])) {
                                    foreach ($parse_page['vacancies'] as $vacancy) {
                                        $this->db->beginTransaction();
                                        try {
                                            $model->saveVacancy((int)$source->id, $vacancy, $page_options);

                                            $this->apiMetrics->incPrometheus('core2_jobs_vacancy_process', 1, [
                                                'labels'   => ['source' => $source->name],
                                                'job'      => 'core2',
                                                'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                                            ]);

                                            $this->db->commit();
                                        } catch (\Exception $e) {
                                            $this->db->rollback();
                                            $error_messages[] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
                                        }
                                    }
                                }

                                if ( ! empty($error_messages)) {
                                    $this->sendErrorMessage('Ошибки при обработке вакансий', $error_messages);

                                    $page->status = 'error';
                                    $page->note   =  implode(PHP_EOL.PHP_EOL, $error_messages);
                                    $page->save();

                                } else {
                                    $model->saveSummary((int)$source->id, $parse_page, $page_options);

                                    $page->status = 'complete';
                                    $page->note   = null;
                                    $page->save();
                                }


                            } catch (\Exception $e) {
                                $page->status = 'error';
                                $page->note   = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
                                $page->save();

                                $this->sendErrorMessage('Ошибка при обработке вакансий', $e);
                            }
                        });
                    }

                    $parallel->start();


                    $pages_resume = $this->modJobs->dataJobsPages->getRowsByTypeStatus($source_name, ['resume_categories', 'resume_professions'], 'pending');

                    foreach ($pages_resume as $page) {

                        $parallel->addTask(function () use ($source, $page) {

                            $model = new Jobs\Index\Model();

                            $page->status = 'process';
                            $page->save();

                            $error_messages = [];

                            try {
                                $date         = new \DateTime($page->date_created);
                                $file_content = $model->getSourceFile('jobs', $date, $page->file_name);

                                $page_content = gzuncompress(base64_decode($file_content['content']));
                                $page_options = $page->options ? json_decode($page->options, true) : [];
                                $page_options['date_created'] = $page->date_created;

                                $source_class = $model->getSource($source->name);
                                $parse_page   = $source_class->parseResumeList($page_content, $page_options);


                                if ( ! empty($parse_page['resume'])) {
                                    foreach ($parse_page['resume'] as $resume) {
                                        $this->db->beginTransaction();
                                        try {
                                            $model->saveResume((int)$source->id, $resume, $page_options);

                                            $this->apiMetrics->incPrometheus('core2_jobs_resume_process', 1, [
                                                'labels'   => ['source' => $source->name],
                                                'job'      => 'core2',
                                                'instance' => $_SERVER['SERVER_NAME'] ?? 'production',
                                            ]);

                                            $this->db->commit();

                                        } catch (\Exception $e) {
                                            $this->db->rollback();
                                            $error_messages[] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
                                        }
                                    }
                                }


                                if ( ! empty($error_messages)) {
                                    $this->sendErrorMessage('Ошибки при обработке вакансий', $error_messages);

                                    $page->status = 'error';
                                    $page->note   =  implode(PHP_EOL.PHP_EOL, $error_messages);
                                    $page->save();

                                } else {
                                    $model->saveSummary((int)$source->id, $parse_page, $page_options);

                                    $page->status = 'complete';
                                    $page->note   = null;
                                    $page->save();
                                }

                            } catch (\Exception $e) {
                                $page->status = 'error';
                                $page->note   = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
                                $page->save();

                                $this->sendErrorMessage('Ошибка при обработке резюме', $e);
                            }
                        });
                    }

                    $parallel->start();
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


    /**
     * @param string $source_name
     * @param int    $page_id
     * @param string $parse_type
     * @return void
     * @throws Zend_Config_Exception
     * @throws Zend_Db_Table_Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws Exception
     */
    public function showSources(string $source_name, int $page_id, string $parse_type = 'vacancies'): void {

        $model   = new Jobs\Index\Model();
        $sources = $model->getSources();

        if (empty($sources[$source_name])) {
            throw new \Exception('Указанный ресурс не найден');
        }

        $page = $this->modJobs->dataJobsPages->find($page_id)->current();

        if (empty($page)) {
            throw new \Exception('Указанная страница не найдена');
        }


        $file_content = $model->getSourceFile('jobs', new \DateTime($page->date_created), $page->file_name);

        $source_class = $sources[$source_name];
        $page_content = gzuncompress(base64_decode($file_content['content']));

        if ($parse_type == 'vacancies') {
            $parse_page = $source_class->parseVacanciesList($page_content);

        } else {
            $parse_page = $source_class->parseResumeList($page_content);
        }


        echo '<pre>';
        print_r($parse_page);
        echo '</pre>';

        echo '<pre>';
        echo htmlspecialchars($page_content);
        echo '</pre>';
    }
}
