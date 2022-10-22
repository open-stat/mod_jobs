<?php
use Core2\Mod\Minsk115;

require_once DOC_ROOT . "core2/inc/ajax.func.php";
require_once 'classes/autoload.php';


/**
 * @property \ModSourcesController $modSources
 */
class ModAjax extends ajaxFunc {
    /**
     * @param array $data
     * @return xajaxResponse
     * @throws Zend_Db_Adapter_Exception
     * @throws Exception
     */
    public function axSavePage(array $data): xajaxResponse {

        $fields = [
            'title'   => 'req',
            'url'     => 'req',
            'content' => 'req',
        ];

        if ($this->ajaxValidate($data, $fields)) {
            return $this->response;
        }


        if ( ! empty($data['control']['source_url'])) {
            $parse_url = parse_url($data['control']['source_url']);
            $data['control']['source_domain'] = $parse_url['host'] ?? '';
        }


        $content = $data['control']['content'];
        unset($data['control']['content']);


        $page_id = $this->saveData($data);

        $page_content = $this->modSources->dataSourcesPagesContents->getRowByPageId($page_id);
        $page_content->content = $content;
        $page_content->hash    = md5($content);
        $page_content->save();


        if (empty($this->error)) {
            $this->response->script("CoreUI.notice.create('Сохранено');");
            $this->response->script("load('index.php?module=sources&action=index');");
        }


        $this->done($data);
        return $this->response;
    }
}
