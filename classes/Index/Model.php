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


        // Если пришла старая закрытая вакансия
        $vacancy_close = $this->modJobs->dataJobsEmployersVacancies->getRowBySourceUrlDate($source_id, $vacancy['url'], $options['date_created']);
        if ( ! empty($vacancy_close)) {
            return 0;
        }


        $salary_min_byn = null;
        $salary_max_byn = null;

        if ( ! empty($vacancy['currency'])) {
            $date_currency = new \DateTime($options['date_created']);

            if ( ! empty($vacancy['salary_min'])) {
                $salary_min_byn = $this->modJobs->getConvertCurrency($vacancy['salary_min'], $vacancy['currency'], $date_currency);
            }

            if ( ! empty($vacancy['salary_max'])) {
                $salary_max_byn = $this->modJobs->getConvertCurrency($vacancy['salary_max'], $vacancy['currency'], $date_currency);
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
                'date_publish'     => $options['date_created'],
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
                $vacancy_row->date_close = $options['date_created'];
                $vacancy_row->save();

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
                    'date_publish'     => $options['date_created'],
                    'date_close'       => null,
                ]);

            } else {
                $vacancy_row->salary_min_byn = $salary_min_byn;
                $vacancy_row->salary_max_byn = $salary_max_byn;
            }

            $vacancy_row->save();
        }


        if ( ! empty($salary_min_byn) && ! empty($salary_max_byn)) {
            $vacancy_salary = $this->modJobs->dataJobsEmployersVacanciesSalary->getRowByVacancyDate($vacancy_row->id,  $options['date_created']);

            if (empty($vacancy_salary)) {
                $vacancy_salary = $this->modJobs->dataJobsEmployersVacanciesSalary->createRow([
                    'vacancy_id'     => $vacancy_row->id,
                    'date_salary'    => $options['date_created'],
                    'salary_min_byn' => $salary_min_byn,
                    'salary_max_byn' => $salary_max_byn,
                ]);
                $vacancy_salary->save();
            }
        }


        // Категории
        if ( ! empty($options['category_name']) &&
             ! empty($options['category_title'])
        ) {
            $category = $this->modJobs->dataJobsCategories->getRowByCategoryName($options['category_name'], $options['category_title']);

            if (empty($category)) {
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
                $vacancy_profession = $this->modJobs->dataJobsEmployersVacanciesCategories->getRowByVacancyCategoryId($vacancy_row->id, $profession->id);

                if (empty($vacancy_profession)) {
                    $vacancy_profession = $this->modJobs->dataJobsEmployersVacanciesCategories->createRow([
                        'vacancy_id'    => $vacancy_row->id,
                        'profession_id' => $profession->id,
                    ]);
                    $vacancy_profession->save();
                }
            }
        }

        return (int)$vacancy_row->id;
    }
}