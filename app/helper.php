<?php

/**
 * @param $arr
 *
 * @return mixed
 * 冒泡排序
 */
function bubbleSort($arr)
{
    $len = count($arr);

    for ($i = 1; $i < $len; $i++) {
        for ($k = 0; $k < $len - $i; $k++) {
            if ($arr[$k] > $arr[$k + 1]) {
                $tmp = $arr[$k + 1];
                $arr[$k + 1] = $arr[$k];
                $arr[$k] = $tmp;
            }
        }
    }
    return $arr;
}

/**
 * @param $arr
 *
 * @return mixed
 * 选择排序
 */
function selectSort($arr)
{
    //$i 当前最小值的位置， 需要参与比较的元素
    for ($i = 0, $len = count($arr); $i < $len - 1; $i++) {
        //先假设最小的值的位置
        $p = $i;
        //$j 当前都需要和哪些元素比较，$i 后边的。
        for ($j = $i + 1; $j < $len; $j++) {
            //$arr[$p] 是 当前已知的最小值
            if ($arr[$p] > $arr[$j]) {
                //比较，发现更小的,记录下最小值的位置；并且在下次比较时，应该采用已知的最小值进行比较。
                $p = $j;
            }
        }
        //已经确定了当前的最小值的位置，保存到$p中。
        //如果发现 最小值的位置与当前假设的位置$i不同，则位置互换即可
        if ($p !== $i) {
            $tmp = $arr[$p];
            $arr[$p] = $arr[$i];
            $arr[$i] = $tmp;
        }
    }
    return $arr;
}

/**
 * @param $arr
 *
 * @return array|false
 * 快速排序
 */
function quickSort($arr)
{
    //判断参数是否是一个数组
    if (!is_array($arr)) {
        return false;
    }
    //递归出口:数组长度为1，直接返回数组
    $length = count($arr);
    if ($length <= 1) {
        return $arr;
    }
    //数组元素有多个,则定义两个空数组
    $left = $right = [];
    //使用for循环进行遍历，把第一个元素当做比较的对象
    for ($i = 1; $i < $length; $i++) {
        //判断当前元素的大小
        if ($arr[$i] < $arr[0]) {
            $left[] = $arr[$i];
        } else {
            $right[] = $arr[$i];
        }
    }
    //递归调用
    $left = quickSort($left);
    $right = quickSort($right);
    //将所有的结果合并
    return array_merge($left, array($arr[0]), $right);
}

function seqSearch($arr, $k) {
    foreach ($arr as $key => $val) {
        if ($val === $k) {
            return $key;
        }
    }
    return -1;
}

function random_sms_code() {
    return random_int(200001, 999999);
}