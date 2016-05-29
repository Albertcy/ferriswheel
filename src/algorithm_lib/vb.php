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
        0 == ($byte & 128) ? ($ret[++ $i] = null) : ($ret[$i] <<= 7);
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

function encodeFile($file)
{
    $handleR = @fopen("$file", "rb");
    $outputFile = 'encode_' . time() . '.txt';
    $handleW = @fopen($outputFile, 'wb');
    if ($handleR) {
        while (($line = fgets($handleR, 4096)) !== false) {
            $strings = explode(' ', $line);
            $nums = explode(',', $strings[1]);
            $encodeNums = '';
            foreach ($nums as $num) {
                $encodeNums .= vbEncode($num);
            }
            $outputLine = pack('N2', strlen($strings[0]), strlen($encodeNums)) . $strings[0] . $encodeNums;
            fwrite($handleW, $outputLine);
        }
        if (! feof($handleR)) {
            echo "Error: unexpected fgets() fail\n";
        }
        fclose($handleR);
        fclose($handleW);
    }
}

function decodeFile($file)
{
    $handleR = @fopen($file, 'rb');
    $i = 0;
    while (! feof($handleR) && '' != ($buffer = fread($handleR, 8))) {
        $i ++;
        $lens = unpack('N2', $buffer);
        $tag = fread($handleR, $lens[1]);
        $numsBits = fread($handleR, $lens[2]);
        $nums = implode(',', vbDecode($numsBits));
        $line =  "{$tag} {$nums}" . PHP_EOL;
        file_put_contents('source_' . time() . 'txt.', $line , FILE_APPEND);
    }
}

//encodeFile('text.txt');
//decodeFile('encode_1464540523.txt');




