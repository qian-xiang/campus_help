<?php
/**
 * checkReferer 验证来源是否合法
 * @return boolean 合法返回真，否则返回假
 */
function checkReferer(){
    $refererArray = ['https://servicewechat.com/12345678/0/page-frame.html','https://servicewechat.com/12345678/devtools/page-frame.html'];
    $refererGoal = $_SERVER['HTTP_REFERER'];
    $response = in_array($refererGoal, $refererArray)? true : false;

    return $response;
}
