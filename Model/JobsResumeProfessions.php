<?php


/**
 *
 */
class JobsResumeProfessions extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_resume_professions';


    /**
     * @param int $resume_id
     * @param int $profession_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByResumeProfessionId(int $resume_id, int $profession_id): ?Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("resume_id = ?", $resume_id)
            ->where("profession_id = ?", $profession_id);

        return $this->fetchRow($select);
    }
}