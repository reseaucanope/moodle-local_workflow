<?php

$tasks = array(
    array(
        'classname' => 'local_workflow\task\workflow_notification_task',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '02',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
        'disabled' => 0
    ),
    array(
        'classname' => 'local_workflow\task\purge_trashed_courses_task',
        'blocking' => 0,
        'minute' => '17',
        'hour' => '3',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
        'disabled' => 1
    )
);