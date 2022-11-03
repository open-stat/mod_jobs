<?php


/**
 *
 */
class JobsEmployersVacanciesProfessions extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_employers_vacancies_professions';


    /**
     * @param int $vacancy_id
     * @param int $profession_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByVacancyProfessionId(int $vacancy_id, int $profession_id): ?Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("vacancy_id = ?", $vacancy_id)
            ->where("profession_id = ?", $profession_id);

        return $this->fetchRow($select);
    }
}