<?php


/**
 *
 */
class JobsCategories extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_jobs_categories';


    /**
     * @param string $category_name
     * @param string $category_title
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByCategoryName(string $category_name, string $category_title = ''): ?Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("name = ?", $category_name);

        $row = $this->fetchRow($select);

        if (empty($row) && $category_title) {
            $row = $this->createRow([
                'name'  => $category_name,
                'title' => $category_title,
            ]);
            $row->save();
        }

        return $row;
    }
}