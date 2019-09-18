<?php

/* global const */
$g_threshold = 20;

/* get URL val */
if(isset($_REQUEST['type'])) $type = $_REQUEST['type'];
if(isset($_REQUEST['usr']))$usr = $_REQUEST['usr'];
if(isset($_REQUEST['val'])) $val = $_REQUEST['val'];
if(isset($_REQUEST['img'])) $img = $_REQUEST['img'];
if(isset($_REQUEST['id'])) $id = $_REQUEST['id'];

/* cnt to db */
$conn = db__connect();
$lastItem = end(db__getData($conn, "account"));
$datetime = date('Y-m-d H:i:s');
$tmpItem = $lastItem;
$tmpItem['datetime'] = $datetime;

/* usr array */
$usrArray = ['liu', 'yang', 'li', 'jia', 'zheng'];


/* total count func */
function getAvg($array){
    return ($array['liu'] + $array['yang'] + $array['li'] + $array['jia'] + $array['zheng']) / 5;
}

function getTotal($array){
    return getAvg($array) * 5;
}

if($type == "payment")
{
    if(!(isset($usr) && isset($val) && $val > 0 && isset($img))){
        die();
    }

    if(!($usr == "liu" || $usr == "yang" || $usr == "li" || $usr == "jia" || $usr == "zheng")){
        die();
    }

    $tmpItem['type'] = "payment";
    $tmpItem[$usr] += $val;
    $tmpItem[$usr.'_'] += $val;
    $tmpItem['total'] = getTotal($tmpItem);
    $tmpItem['avg'] = getAvg($tmpItem);

    db__pushData($conn, "account", $tmpItem);
    db__pushData($conn, "detail", array(
        "dateTime" => $datetime,
        "usr" => $usr,
        "val" => $val,
        "img" => $img,
        "statue" => 1
    ));

    $tmpItem = checkCurrent($tmpItem);
    
}


if($type == "confirm")
{
    if(!(isset($id))){
        die();
    }

    $current_item = db__getData($conn, "current", 'id', $id);

    if($current_item['status'] == 1){
        die();
    }

    $tmpItem['type'] = "confirm";
    $tmpItem[$current_item['usr_from']] += $current_item['val'];
    $tmpItem[$current_item['usr_to']] -= $current_item['val'];
    $tmpItem['total'] = getTotal($tmpItem);
    $tmpItem['avg'] = getAvg($tmpItem);

    db__pushData($conn, "account", $tmpItem);
    db__pushData($conn, "current", array(
        "status" => 1
    ), array(
        "id" => $id
    ));

}


echo json_encode(array(
    "code" => 200,
    "data" => $tmpItem
));

die();








function checkCurrent($tmpItem){

    if(checkCurrentMore($tmpItem) && checkCurrentLess($tmpItem)){
        $usr_to = checkCurrentMore($tmpItem);
        $usr_from = checkCurrentLess($tmpItem);
        $tmpItem = setCurrent($tmpItem, $usr_to, $usr_from);
        $tmpItem['type'] = "current";
        db__pushData($conn, "account", $tmpItem);
        db__pushData($conn, "current", array(
            "datetime" => $GLOBALS['datetime'],
            "usr_to" => $usr_to,
            "usr_from" => $usr_from,
            "val" => $GLOBALS['g_threshold'],
            "status" => 0
        ));    
    }
    return $tmpItem;
}

function setCurrent($tmpItem, $usr_to, $usr_from){

    $tmpItem[$usr_to.'_'] -= $GLOBALS['g_threshold'];
    $tmpItem[$usr_from.'_'] += $GLOBALS['g_threshold'];
    return $tmpItem;
}

function checkCurrentMore($tmpItem){

    foreach($GLOBALS['usrArray'] as $item){
        if($tmpItem[$item.'_'] - $tmpItem['avg'] > $GLOBALS['g_threshold']) return $item;
    }
    return false;
}
function checkCurrentLess($tmpItem){

    foreach($GLOBALS['usrArray'] as $item){
        if($tmpItem[$item.'_'] + $tmpItem['avg'] < $GLOBALS['g_threshold']) return $item;
    }
    return false;
}

