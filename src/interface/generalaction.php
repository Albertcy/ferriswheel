<?php

/**
 * 通用行为接口 处理常用 action（MVC）
 * @author Ricahrd colson
 * @date 2016-03-23
 * @version $1
 */
interface generalaction {

    /**
     * 获取列表参数
     */
    public function getListRequestParams();

    /**
     * 获取详情信息接口
     */
    public function getDetailRequestParams();

    /**
     * 获取列表信息
     */
    public function getListInfos();

    /**
     * 获取详情信息
     */
    public function getDetailInfos();
}