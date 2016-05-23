 <?php
    function codeNumber($n){
        $bytes = [];
        while (true){
            array_unshift($bytes, bcmod($n, 128));
            if($n < 128){
                break;
            }else{
                $n = intval($n/128);
            }
        }
        $bytes[count($bytes) - 1] += 128;
        return $bytes;
    }

    function encode($numbers){
        $bytestream = [];
        foreach ($numbers as $n){
            $bytestream = array_merge($bytestream, codeNumber($n));
        }
        return $bytestream;
    }

    function decode($bytestream){
        $numbers = [];
        $n = 0;
        for ($i = 0; $i < count($bytestream); $i++){
            if($bytestream[$i] < 128){
                $n = 128 * $n + $bytestream[$i];
            }else{
                $n = 128 * $n + ($bytestream[$i] - 128);
                array_push($numbers, $n);
                $n = 0;
            }
        }
        return $numbers;
    }
    $a = encode([5, 130, 288]);
    print_r($a);
    var_dump(decode($a));