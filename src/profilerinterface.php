<?php
/**
 * �������ӿ�
 * @author albert
 */
interface ProfilerInterface
{

    /**
     * ��ʼ����
     */
    public function start();

    /**
     * ��������
     */
    public function end();

    /**
     * �������Ƿ����
     *
     * @return bool
     */
    public function isProfilerEnable();

    /**
     * ��ʼ��
     */
    public function ini();
}
