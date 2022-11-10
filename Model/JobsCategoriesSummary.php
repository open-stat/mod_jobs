<?php


/**
 *
 */
class JobsCategoriesSummary extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_categories_summary';


    /**
     * @param int      $source_id
     * @param int      $category_id
     * @param DateTime $date_created
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByCategoryIdDate(int $source_id, int $category_id, \DateTime $date_created):? \Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("source_id = ?", $source_id)
            ->where("category_id = ?", $category_id)
            ->where("date_summary = ?", $date_created->format('Y-m-d'));

        return $this->fetchRow($select);
    }
}