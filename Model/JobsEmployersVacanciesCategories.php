<?php


/**
 *
 */
class JobsEmployersVacanciesCategories extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_employers_vacancies_categories';


    /**
     * @param int $vacancy_id
     * @param int $category_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByVacancyCategoryId(int $vacancy_id, int $category_id): ?Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("vacancy_id = ?", $vacancy_id)
            ->where("category_id = ?", $category_id);

        return $this->fetchRow($select);
    }
}