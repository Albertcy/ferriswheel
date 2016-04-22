<?php

/**
 * 通用行为接口 处理常用 action（MVC）
* @author Ricahrd colson
* @date 2016-03-23
* @version $1
*/
interface comm_interface_generaldetailaction {

    /**
     * 获取查询详情信息请求参数
     */
    public function getDetailRequestParams();


    /**
     * 列表查询参数校验
     * @param array $params
     * @return array
     */
    public function isDetailQueryPramasVefiried($params);


    /**
     * 获取详情信息
     */
    public function getDetailInfos($params);
}