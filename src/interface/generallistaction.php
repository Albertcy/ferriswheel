<?php

/**
 * 通用行为接口 处理常用 action（MVC）
 * @author Ricahrd colson
 * @date 2016-03-23
 * @version $1
 */
interface comm_interface_generallistaction {

    /**
     * 获取查询信息列表请求参数
     */
    public function getListRequestParams();

    /**
     * 列表参数校验
     * @return bool
     */
    public function isListParamsVerified($params);

    /**
     * 获取列表信息
     */
    public function getListInfos($params);
}