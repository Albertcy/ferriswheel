<?php

/**
 * 通用行为接口 处理常用 action（MVC）
 * @author Ricahrd colson
 * @date 2016-04-20
 * @version $1
 */
interface GeneralSave {

    /**
     * 获取合法的表单名
     */
    public function getDefSaveFields();

    /**
     * 获取存储字段 和 表单映射
     */
    public function getFeildDataMap();

    /**
     * 处理表单值
     * @param array $data
     */
    public function getSaveData($data);

    /**
     * 保存数据逻辑
     */
    public function save($data);
}