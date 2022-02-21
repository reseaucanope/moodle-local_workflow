<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

/**
 * Create the optimizer pdf report, don't forget to use unlink($path) when done with it
 * @param int $courseid 
 * @param string $mmcachedSessionKey memcached key for retriving the pdf data
 * @return string Path to the pdf report
 */
function createPdfReport($courseid, $mmcachedSessionKey) {
    global $CFG;

    $tmp_pdf = $CFG->tempdir.'/optimizer_report_'.$courseid.'_'.time().'.pdf';

    $cmd = $CFG->wkhtmltopdf_path.' --image-dpi 600 --footer-right "Page [page] sur [toPage]" "'.$CFG->wwwroot.'/local/workflow/pdfOptimizerReportSources.php?courseid='.$courseid.'&mmsessionKey='.$mmcachedSessionKey.'" '.$tmp_pdf;
    
    $exec_output = array();
    $exec_return = 0;
    //exec($cmd,&$exec_output,&$exec_return);
    exec($cmd,$exec_output);

    return  $tmp_pdf;
}

/**
 * Send the optimizer email with the pdf in attachment
 * @param int $courseid 
 * @param int $startTime timestamp showing the starting time of the optimisation
 * @param user $user email recipient
 * @param string $pdfPath Path to the attachment pdf
 * @return bool Return true if mail was sent ok and false if there was an error
 */
function sendMail($courseid, $startTime, $user, $pdfPath) {
    global $CFG;

    $date = date('d/m/Y', $startTime);
    $time = date('H:i:s', $startTime);
    $course = get_course($courseid);

    $noreplyuser = core_user::get_noreply_user();
    $subject = get_string('optimizer_mail_subject', 'local_workflow', $course->fullname);
    $body = get_string('optimizer_mail_body', 'local_workflow', array('date'=>$date,'time'=>$time, 'coursename'=> $course->fullname));

    
    $pdfname = 'optimisation_platform['.$CFG->academie_name.']-'.$course->id.'_'.$startTime. '['.date('Ymd-Hi', $startTime).'].pdf';

    $res = email_to_user($user, $noreplyuser, $subject, $body, '', $pdfPath, $pdfname);
    return $res;
}
