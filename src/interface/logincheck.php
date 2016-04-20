<?php
/**
 * 登录检测
 * @author Richard
 *
 */
interface LoginCheck {

    /**
     * 验证登录 否则跳转到指定页
     */
    public function verifyLoginOrRedirect();
}