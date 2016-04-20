<?php

/**
 * 通用行为处理 action (MVC)
 * @author Richard
 * @date 2016-03-30
 * @version $1
 */
class general_action_handle {

    /**
     * 列表行为
     */
    public function listAction() {
        $this->verifyLoginOrRedirect();
        if (!$this->verifyListParams($this->getListParams()) || !$this->authentication()) {
            $this->output(array(
                'status' => 0,
                'msg' => $this->getErrMsg() 
            ));
            return;
        }
        
        $this->log(array(
            'op_content' => '列表查询',
            'op_content_json' => json_encode($this->getListParams()),
            'op_type' => model_datashare_dawnoplog::OP_TYPE_READ 
        ));
        $this->output($this->getListInfos());
    }

    /**
     * 详情
     */
    public function detailAction() {
        $this->verifyLoginOrRedirect();
        if (!$this->basicDetailVeriy($this->getDetailParams())) {
            $this->output(array(
                'status' => 0,
                'msg' => model_datashare_dsmsg::mI()->getErrMsg() 
            ));
            return;
        }
        $this->addQueryTimes();
        $this->log(array(
            'op_content' => '详情查询',
            'op_content_json' => json_encode($this->getDetailParams()),
            'op_type' => model_datashare_dawnoplog::OP_TYPE_READ 
        ));
        $this->output($this->getDetailInfos());
    }

    /**
     * 基础校验
     * @param array $params
     * @return boolean
     */
    protected function verifyListParams($params) {
        return $this->getClient()->isListParamsVerified($params);
    }

    /**
     * 通用接口输出
     * @param array $data
     */
    private function output($data) {
        header('Content-type: application/json');
        echo json_encode($data);
    }

    /**
     * 获取列表参数
     * @return array
     */
    private function getListParams() {
        if (empty($this->_listParams)) {
            $this->_listParams = $this->getClient()->getListRequestParams();
        }
        return $this->_listParams;
    }

    /**
     * 列表参数
     * @var array
     */
    private $_listParams;

    /**
     * 获取委托人
     * @return common_action
     */
    private function getClient() {
        return $this->_clientInstance;
    }

    /**
     * 获取错误消息
     */
    private function getErrMsg() {
        return $this->errMsg;
    }

    /**
     * 设置错误消息
     * @param string $msg
     */
    private function setErrmsg($msg) {
        if (empty($this->errMsg)) {
            $this->errMsg = $msg;
        }
        return;
    }

    /**
     * 消息
     * @var string
     */
    private $errMsg;

    /**
     * 调用委托人
     * @var common_action
     */
    private $_clientInstance;
}