<?php
namespace local_workflow\task;

define('PURGE_TRASH_DRYRUN',true);

class purge_trashed_courses_task extends \core\task\scheduled_task {
    /**
     * Return the name of the task
     * @return string
     */
    public function get_name() {
        return get_string('purge_trashed_courses_task', 'local_workflow');
    }

    /**
     * Execute the task
     */
    public function execute() {
        global $DB;
        mtrace('Processing courses purge');
        $sql = "SELECT c.*
FROM {course} c
INNER JOIN {context} cx ON (cx.instanceid=c.id)
WHERE c.timemodified < ".((time()-16070400))."
AND cx.contextlevel = 50
AND cx.path LIKE(SELECT CONCAT('%/',id,'/%') FROM {context} WHERE contextlevel = 40 AND instanceid = (SELECT id FROM {course_categories} WHERE name = 'Corbeille' AND depth = 1))";

        // Iterate over the courses
        $courses = $DB->get_records_sql($sql);
        if(count($courses) > 0){
            mtrace(count($courses).' courses to delete');

            foreach ($courses as $course) {
                mtrace('Delete course with id '.$course->id.' with timemodified '.$course->timemodified);
                if (!PURGE_TRASH_DRYRUN) {
                    try {
                        $course->deletesource = 'restore'; // disable recyclebin
                        delete_course($course);
                    }catch (\Exception | \moodle_exception $e){
                        mtrace('Exception : '.$e->getCode().' : '.$e->getMessage()."\n".$e->getTraceAsString());
                    }
                }
            }
        }else{
            mtrace('No course to delete');
        }
        
        mtrace('End of courses purge process');
    }
}
