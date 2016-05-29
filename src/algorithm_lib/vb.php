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
        $i ++;
        $ret = pack('C', ($arg & 127 | 128)) . $ret;
        $arg >>= 7;
    }
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
    $ret = array();
    $i = - 1;
    $bits = unpack('C*', $arg);
    foreach ($bits as $byte) {
        0 == ($byte & 128) ? ++ $i : ($ret[$i] <<= 7);
        $ret[$i] |= ($byte & 127);
    }
    return $ret;
}

/**
 * variable byte code encode Integer Val
 *
 * @param int $arg            
 * @return int
 */
function vbEncodeBit($arg)
{
    $ret = null;
    $i = 0;
    while ($arg > 127) {
        $ret = $ret | (($arg & 127 | 128) << (8 * $i ++));
        $arg >>= 7;
    }
    $i > 0 ? ($ret |= $arg << (8 * $i)) : ($ret = $arg | 128);
    return $ret;
}

/**
 *
 * @param bytecode $arg            
 * @return number
 */
function vbDecodebit($arg)
{
    $ret = null;
    $i = 0;
    while (128 == ($arg & 128)) {
        $ret |= $arg & 127 << (7 * $i ++);
        $arg >>= 8;
    }
    $i == 0 ? $ret = $arg & 127 : $ret |= $arg << (7 * $i);
    return $ret;
}

$enNum = vbEncode(12238) . vbEncode(2323) . vbEncode(35232) .vbEncode(23423);
print_r(vbDecode($enNum));
echo strlen($enNum);


