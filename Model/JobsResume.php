<?php


/**
 *
 */
class JobsResume extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_resume';


    /**
     * @return void
     */
    public function resetStatusLoadProcess(): void {

        $where   = [];
        $where[] = "status_load = 'process'";
        $where[] = "date_last_update < DATE_SUB(NOW(), INTERVAL 10 MINUTES)";

        $this->update([
            'status_load' => 'pending'
        ], $where);
    }


    /**
     * @return void
     */
    public function resetStatusParseProcess(): void {

        $where   = [];
        $where[] = "status_parse = 'process'";
        $where[] = "date_last_update < DATE_SUB(NOW(), INTERVAL 10 MINUTES)";

        $this->update([
            'status_parse' => 'pending'
        ], $where);
    }


    /**
     * @param int    $source_id
     * @param string $status
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getRowsByStatusLoad(int $source_id, string $status): Zend_Db_Table_Rowset_Abstract {

        $select = $this->select()
            ->where("source_id = ?", $source_id)
            ->where("status_load = ?", $status);

        return $this->fetchAll($select);
    }


    /**
     * @param int    $source_id
     * @param string $status
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getRowsByStatusParse(int $source_id, string $status): Zend_Db_Table_Rowset_Abstract {

        $select = $this->select()
            ->where("source_id = ?", $source_id)
            ->where("status_parse = ?", $status);

        return $this->fetchAll($select);
    }


    /**
     * @param int    $source_id
     * @param string $url
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowBySourceUrl(int $source_id, string $url): ?Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("source_id = ?", $source_id)
            ->where("url = ?", $url)
            ->order("date_publish DESC")
            ->limit(1);

        return $this->fetchRow($select);
    }


    /**
     * @param int      $source_id
     * @param string   $url
     * @param DateTime $date
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowBySourceUrlDate(int $source_id, string $url, \DateTime $date): ?Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("source_id = ?", $source_id)
            ->where("url = ?", $url)
            ->where("IF (date_close IS NOT NULL, ? BETWEEN date_publish AND date_close, 0)", $date->format('Y-m-d'))
            ->limit(1);

        return $this->fetchRow($select);
    }
}