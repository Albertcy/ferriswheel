<?php

/**
 * 通用行为处理 action (MVC)
 * @author Richard
 * @date 2016-03-30
 * @version $1
 */
class comm_routine_generalaction {

    /**
     * 操作日志类型
     * @var int
     */
    const OP_TYPE_READ = 1;

    const OP_TYPE_ADD = 2;

    const OP_TYPE_DELETE = 3;

    const OP_TYPE_EDIT = 4;

    /**
     * 列表行为
     */
    public function listAction() {
        if(!$this->getClient() instanceof generalaction){
            return ;
        }
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
            'op_type' => self::OP_TYPE_READ 
        ));
        $this->output($this->getListInfos());
    }

    /**
     * 详情
     */
    public function detailAction() {
        if(!$this->getClient() instanceof generalaction){
            return ;
        }
        $this->verifyLoginOrRedirect();
        if (!$this->basicDetailVeriy($this->getDetailParams())) {
            $this->output(array(
                'status' => 0,
                'msg' => $this->getErrMsg() 
            ));
            return;
        }
        $this->addQueryTimes();
        $this->log(array(
            'op_content' => '详情查询',
            'op_content_json' => json_encode($this->getDetailParams()),
            'op_type' => self::OP_TYPE_READ 
        ));
        $this->output($this->getDetailInfos());
    }

    /**
     * 保存
     */
    public function saveAction() {
        if (!$this->getClient() instanceof GeneralSave) {
            return;
        }
        $data = $this->getInsertDataRoutine();
        if (!$this->virifyUserParams($data) || !$this->authentication()) {
            $this->output(array(
                'status' => 0,
                'msg' => $this->getErrMsg() 
            ));
            return;
        }
        
        $this->log(array(
            'op_content' => ($data['id'] ? ' 编辑 ' : ' 新增 '),
            'op_content_json' => json_encode($this->getPostData()),
            'op_type' => ($data['id'] ? self::OP_TYPE_EDIT : self::OP_TYPE_ADD) 
        ));
        $flag = $this->getClient()->save($data);
        
        $this->output(array(
            'status' => (int) $flag,
            'msg' => ($flag ? '成功' : '失败') 
        ));
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
     * 获取要存储的字段名
     * @return array
     */
    private function getDefSaveFields() {
        $params = array(
            'username' => '',
            'cell' => '',
            'mail' => '',
            'cityids' => 0,
            'serviceid' => 0,
            'roleids' => '0',
            'id' => 0,
            'name' => '',
            'company' => '',
            'status' => 0 
        );
        return $params;
    }

    /**
     * 获取存储字段 和 表单映射
     */
    private function getFeildDataMap() {
        $map = array(
            'pwd' => 'password',
            'zhname' => 'name',
            'roleids' => 'role_id',
            'cityids' => 'city_id',
            'serviceid' => 'menu_id' 
        );
        return $map;
    }

    /**
     * 获取表单需要写入的数据
     * @param array $data
     */
    private function getInsertData($data) {
        $map = $this->getFeildDataMap();
        $rec = array();
        foreach($data as $k => $v) {
            if (!empty($map[$k])) {
                $rec[$map[$k]] = $v;
            } else {
                $rec[$k] = $v;
            }
        }
        return $rec;
    }

    /**
     * 获取过滤的存储数据
     * @return array
     */
    private function getFilteredData() {
        $params = $this->getClient()->getDefSaveFields();
        return array_filter(array_intersect_key($params, $this->getPostData()), $params);
    }

    /**
     * 获取表单数据
     * @return array
     */
    private function getPostData() {
        return $_POST;
    }

    /**
     * 处理请求参数
     * @return type|NULL|unknown|string
     */
    private function getInsertDataRoutine() {
        $client = $this->getClient();
        return $this->getInsertData($this->getClient()->getSaveData($this->getFilteredData()));
    }

    /**
     * 验证跳转
     */
    private function verifyLoginOrRedirect() {
        if ($this->getClient() instanceof LoginCheck) {
            $this->getClient()->verifyLoginOrRedirect();
        }
    }

    /**
     * 权限校验
     */
    private function authentication() {
        return $this->getClient()->authentication();
    }

    /**
     * 日志记录
     * @param array $data
     */
    private function log($data) {
        return $this->getClient()->log($data);
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
        if (!$this->getClient() instanceof generalaction) {
            return;
        }
        if (empty($this->_listParams)) {
            $this->_listParams = $this->getClient()->getListRequestParams();
        }
        return $this->_listParams;
    }

    /**
     * 获取详情参数
     * @return array
     */
    private function getDetailParams() {
        if (empty($this->_detailParams)) {
            $this->_detailParams = $this->getClient()->getDetailRequestParams();
        }
        return $this->_detailParams;
    }

    /**
     * 列表参数
     * @var array
     */
    private $_listParams;

    /**
     * 获取委托对象
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
     * @var GeneralAction|GeneralAuthorize|GeneralLog|LoginCheck|GeneralSave
     */
    private $_clientInstance;
}