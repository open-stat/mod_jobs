<?php


/**
 *
 */
class JobsProfessionsSummary extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_professions_summary';


    /**
     * @param int      $source_id
     * @param int      $profession_id
     * @param DateTime $date_created
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByProfessionIdDate(int $source_id, int $profession_id, \DateTime $date_created):? \Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("source_id = ?", $source_id)
            ->where("profession_id = ?", $profession_id)
            ->where("date_summary = ?", $date_created->format('Y-m-d'));

        return $this->fetchRow($select);
    }
}