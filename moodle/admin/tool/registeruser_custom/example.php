<?php

use mod_forum\local\exporters\group;

define('CLI_SCRIPT', true);
require_once(__DIR__ . '../../../../config.php');
require_once(__DIR__ . '/classes/controller.php');

function student() {
    $categoryid = 3;
    $user = tool_registeruser_controller::get_user_by_info('', '', 'student');
    tool_registeruser_controller::unregister_student($user -> id, $categoryid);
    #tool_registeruser_controller::register_student($user_info, $categoryid);
}

function teacher() {
    $courseid = 2;
    $user = tool_registeruser_controller::get_user_by_info('teacher', 'teacher', '');
    #tool_registeruser_controller::unregister_teacher($user->id, $courseid);
    tool_registeruser_controller::unregister_teacher($user->id, $courseid);
    
    #tool_registeruser_controller::register_teacher($user_info, $courseid);
}

function group() {
    $categoryid = 5;
    $group = tool_registeruser_controller::get_group_by_info('test group');
    #tool_registeruser_controller::register_group($group -> id, $categoryid);
    tool_registeruser_controller::unregister_group($group -> id, $categoryid);
}

function CSV() {
    $content = ',,,test group,Г,,5;';
    $array_info = tool_registeruser_controller::process_data(($content));
    foreach ($array_info as $row_info) {
        echo($row_info . '\n');
    }
}

teacher();
#student();
group();
