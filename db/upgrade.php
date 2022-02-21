<?php

function xmldb_local_workflow_upgrade($oldversion) {
    global $DB;
    
    $dbman = $DB->get_manager();
    
    if ($oldversion < 2020121100) {
        
        // remove duplicate entries
        $duplies = $DB->get_records_sql("SELECT course_id, COUNT(id) AS cnt, MAX(id) AS maxid FROM {course_trash_category} GROUP BY course_id HAVING cnt > 1");
        
        foreach ($duplies AS $duply){
            $records = $DB->get_records('course_trash_category',array('course_id'=>$duply->course_id), 'id DESC');
            
            foreach ($records AS $record){
                if ($duply->maxid != $record->id){
                    $DB->delete_records('course_trash_category',array('id'=>$record->id));
                }
            }
        }
        
        $table = new xmldb_table('course_trash_category');
        
        $index = new xmldb_index('uni_course', XMLDB_KEY_UNIQUE, array('course_id'));
        $dbman->add_index($table,$index);
        
        // course_management savepoint reached
        upgrade_plugin_savepoint(true, 2020121100, 'local',  'workflow');
    }
    
    
    
    return true;
}
