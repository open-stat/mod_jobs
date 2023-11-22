<?php


/**
 *
 */
class JobsPages extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_pages';


    /**
     * @return void
     */
    public function resetStatusProcess(): void {

        $where   = [];
        $where[] = "status = 'process'";
        $where[] = "date_last_update < DATE_SUB(NOW(), INTERVAL 10 MINUTE)";

        $this->update([
            'status' => 'pending'
        ], $where);
    }


    /**
     * @param string $source_name
     * @param string $type
     * @param string $status
     * @param int    $limit
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getRowsByTypeStatus(string $source_name, array $type, string $status, int $limit = 5000): Zend_Db_Table_Rowset_Abstract {

        $select = $this->select()
            ->where("source_name = ?", $source_name)
            ->where("type IN(?)", $type)
            ->where("status = ?", $status)
            ->order('date_created ASC')
            ->order('id ASC')
            ->limit($limit);

        return $this->fetchAll($select);
    }


    /**
     * @param string $source_name
     * @param string $type
     * @param array  $page
     * @return int
     * @throws Exception
     */
    public function addPage(string $source_name, string $type, array $page): int {

        if (empty($page['url']) || empty($page['content'])) {
            throw new \Exception('Не переданы обязательные параметры');
        }

        $row = $this->createRow([
            'source_name' => $source_name,
            'type'        => $type,
            'status'      => 'pending',
            'url'         => $page['url'],
            'content'     => gzcompress($page['content'], 9),
            'options'     => ! empty($page['options']) ? json_encode($page['options']) : null,
        ]);

        return $row->save();
    }
}