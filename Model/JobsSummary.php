<?php


/**
 *
 */
class JobsSummary extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_summary';


    /**
     * @param DateTime $date_created
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByDate(\DateTime $date_created):? \Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("DATE_FORMAT(date_summary, '%Y-%m-%d') = ?", $date_created->format('Y-m-d'));

        return $this->fetchRow($select);
    }
}