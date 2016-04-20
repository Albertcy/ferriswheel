<?php

/**
 * 通用日志接口
 * @author Richard
 */
interface GeneralLog {

    /**
     *
     * @param array $data eg: array(
     *        'op_content' => '详情查询',
     *        'op_content_json' => json_encode($this->getDetailParams()),
     *        'op_type' => model_datashare_dawnoplog::OP_TYPE_READ
     *        )
     */
    public function log($data);
}