<?php
/**
 * 登录检测
 * @author Richard
 *
 */
interface comm_interface_logincheck {

    /**
     * 验证登录 否则跳转到指定页
     */
    public function verifyLoginOrRedirect();
}