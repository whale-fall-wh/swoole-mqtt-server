<?php

//删除文件夹以及其下所有文件
function deldir($path)
{
    //如果是目录则继续
    if(!is_dir($path)){
        return false;
    }
    //扫描一个文件夹内的所有文件夹和文件并返回数组
    $p = scandir($path);
    foreach ($p as $val) {
        //排除目录中的.和..
        if ($val =="." || $val =="..") {
            continue;
        }
        //如果是目录则递归子目录，继续操作
        if(is_dir($path.$val)){
            //子目录中操作删除文件夹和文件
            deldir($path.$val.'/');
            //目录清空后删除空文件夹
            @rmdir($path.$val.'/');
        }else{
            //如果是文件直接删除
            unlink($path.$val);
        }
    }
}
