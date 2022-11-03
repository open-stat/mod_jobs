<?php


/**
 *
 */
class JobsEmployersVacanciesActivity extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_employers_vacancies_activity';


    /**
     * @param int      $vacancy_id
     * @param DateTime $date_activity
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByVacancyDate(int $vacancy_id, \DateTime $date_activity): ?Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("vacancy_id = ?", $vacancy_id)
            ->where("date_activity = ?", $date_activity->format('Y-m-d'));

        return $this->fetchRow($select);
    }


    /**
     * @param int      $vacancy_id
     * @param DateTime $date_activity
     * @param array    $options
     * @return void
     * @throws Exception
     */
    public function addDate(int $vacancy_id, \DateTime $date_activity, array $options = []): void {

        $resume_salary = $this->getRowByVacancyDate($vacancy_id, $date_activity);

        if (empty($resume_salary)) {
            $resume_salary = $this->createRow([
                'vacancy_id'     => $vacancy_id,
                'date_activity'  => $date_activity->format('Y-m-d'),
                'salary_min_byn' => $options['salary_min_byn'] ?? null,
                'salary_max_byn' => $options['salary_max_byn'] ?? null,
            ]);
            $resume_salary->save();
        }

        $this->setEmptyDates($vacancy_id);
    }


    /**
     * @param int $vacancy_id
     * @return void
     * @throws Exception
     */
    public function setEmptyDates(int $vacancy_id): void {

        $select = $this->select()
            ->where("vacancy_id = ?", $vacancy_id)
            ->order('date_activity ASC');

        $rows = $this->fetchAll($select);

        if ($rows->count() > 1) {
            $rows_array = $rows->toArray();
            $date_start = new \DateTime(current($rows_array)['date_activity']);
            $date_end   = new \DateTime(end($rows_array)['date_activity']);

            $date_interval = $date_start->diff($date_end);

            if ($date_interval->days > 0) {
                $date = clone $date_start;

                for ($d = 0; $d < $date_interval->days; $d++) {

                    $row_date   = null;
                    $isset_date = false;

                    foreach ($rows as $row) {
                        if ($row->date_activity == $date->format('Y-m-d')) {
                            $isset_date = true;
                            break;
                        } else {
                            if (is_null($row_date) ||
                                ($row->date_activity < $date->format('Y-m-d') && $row->date_activity > $row_date->date_activity)
                            ) {
                                $row_date = $row;
                            }
                        }
                    }

                    if ( ! $isset_date) {
                        $row_new = $this->createRow([
                            'vacancy_id'     => $vacancy_id,
                            'salary_min_byn' => $row_date->salary_min_byn,
                            'salary_max_byn' => $row_date->salary_max_byn,
                            'date_activity'  => $date->format('Y-m-d'),
                        ]);
                        $row_new->save();
                    }

                    $date->modify('+1 day');
                }
            }
        }
    }
}