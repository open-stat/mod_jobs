<?php


/**
 *
 */
class JobsEmployers extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_employers';


    /**
     * @param string $employer_title
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByTitle(string $employer_title): ?\Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("title = ?", $employer_title)
            ->order("date_created DESC");

        return $this->fetchRow($select);
    }
}