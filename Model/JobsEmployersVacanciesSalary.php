<?php


/**
 *
 */
class JobsEmployersVacanciesSalary extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_employers_vacancies_salary';


    /**
     * @param int    $vacancy_id
     * @param string $date_salary
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByVacancyDate(int $vacancy_id, string $date_salary): ?Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("vacancy_id = ?", $vacancy_id)
            ->where("date_salary = ?", $date_salary);

        return $this->fetchRow($select);
    }
}