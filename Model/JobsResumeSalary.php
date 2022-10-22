<?php


/**
 *
 */
class JobsResumeSalary extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_resume_salary';


    /**
     * @param int    $vacancy_id
     * @param string $date_salary
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByVacancyDate(int $vacancy_id, string $date_salary): ?Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("resume_id = ?", $vacancy_id)
            ->where("date_salary = ?", $date_salary);

        return $this->fetchRow($select);
    }
}