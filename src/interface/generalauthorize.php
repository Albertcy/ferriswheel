<?php

/**
 * 通用权限校验
 * @author Richard
 *
 */
interface GeneralAuthorize {

    /**
     * 权限校验
     */
    public function authentication();
}