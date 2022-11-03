<?php


/**
 *
 */
class JobsResumeCategories extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_resume_categories';


    /**
     * @param int $resume_id
     * @param int $category_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByResumeCategoryId(int $resume_id, int $category_id): ?Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("resume_id = ?", $resume_id)
            ->where("category_id = ?", $category_id);

        return $this->fetchRow($select);
    }
}