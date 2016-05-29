<?php

/**
 * @date 2016-05-23
 */
/**
 * variable byte code encode
 *
 * @param int $arg            
 * @return int
 */
function vbEncode($arg)
{
    $ret = null;
    $i = 0;
    while ($arg > 127) {
        // $ret = $ret | (($arg & 127 | 128) << (8 * $i ++));
        $i ++;
        $ret = pack('C', ($arg & 127 | 128)) . $ret;
        $arg >>= 7;
    }
    // $i > 0 ? ($ret |= $arg << (8 * $i)) : ($ret = $arg | 128);
    $i > 0 ? ($ret = pack('C', $arg) . $ret) : ($ret = pack('C', $arg));
    return $ret;
}

/**
 * variable byte code decode
 *
 * @param int $arg            
 * @return int
 */
function vbDecode($arg)
{
    $ret = null;
    $bits = unpack('C*', $arg);
    $slen = count($bits);
    foreach($bits as $byte){
        $ret |= ($byte & 127 )<< (7 * --$slen);
    }
    return $ret;
}

function vbDecodebit($arg){
    $ret = null;
    $i = 0;
    while (128 == ($arg & 128)) {
        $ret |= $arg & 127 << (7 * $i ++);
        $arg >>= 8;
    }
    $i == 0 ? $ret = $arg & 127 : $ret |= $arg << (7 * $i);
    return $ret;
}
