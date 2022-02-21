<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/local/magisterelib/CourseFilesOptimizer.php');


$mmsessionKey = required_param('mmsessionKey', PARAM_RAW);
$courseid = required_param('courseid', PARAM_INT);

// Get the current session
$session = mmcached_get($mmsessionKey);
$data_unserialized = unserialize($session);

function getReadableFileSize($bytes, $decimals = 2) {
    $sz = 'BKMGTP';
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor].'o';
}

function createHtmlLink($url, $name = null) {
    if (!$name) {
        $name = $url;
    }
    return '<a href="'.$url.'">'.$name.'</a>';
}


$course = get_course($data_unserialized->courseid);
$courseUrl = course_get_url($course);
$startTime = $data_unserialized->startTime;

$totalFilesSize = 0;

$unusedfiles_succeed_mods = array();
$unusedfiles_succeed_blocks = array();

if (isset($data_unserialized->unusedfiles_succeed)) {
    foreach($data_unserialized->unusedfiles_succeed as $file) {
        if ($file->sectionnumber) { // modules
            if (!isset($unusedfiles_succeed_mods[$file->sectionnumber])) {
                $sectionInfo = new stdClass();
                $sectionInfo->number = $file->sectionnumber;
                $sectionInfo->name =  (empty($file->sectionname) ? 'Section '.$file->sectionnumber : $file->sectionname);
                $sectionInfo->url = course_get_url($course, $file->sectionnumber);
                $sectionInfo->mods = array();

                $unusedfiles_succeed_mods[$file->sectionnumber] = $sectionInfo;
            }

            if ($file->oid) {
                if (!isset($unusedfiles_succeed_mods[$file->sectionnumber]->mods[$file->oid])) {
                    $moduleInfo = new stdClass();
                    $moduleInfo->cmid = $file->oid;
                    $moduleInfo->type = $file->otype;
                    $moduleInfo->url = new moodle_url($CFG->wwwroot.'/mod/'.$file->otype.'/view.php', array('id'=>$file->oid));  //$CFG->wwwroot.'/mod/'.$file->otype.'/view.php?id='.$file->oid;
                    $moduleInfo->files = array();

                    $unusedfiles_succeed_mods[$file->sectionnumber]->mods[$file->oid] = $moduleInfo;
                }
                
                $unusedfiles_succeed_mods[$file->sectionnumber]->mods[$file->oid]->files[] = $file;

                $totalFilesSize += $file->filesize;
                
            }
        } else { // blocks
            if ($file->oid) {
                if (!isset($unusedfiles_succeed_blocks[$file->oid])) {
                    $blockInfo = new stdClass();
                    $blockInfo->cmid = $file->oid;
                    $blockInfo->type = $file->otype;
                    $blockInfo->files = array();

                    $unusedfiles_succeed_blocks[$file->oid] = $blockInfo;
                }
                
                $unusedfiles_succeed_blocks[$file->oid]->files[] = $file;

                $totalFilesSize += $file->filesize;
            }
        }
    }
}

$filestoconvert_succeed_mods = [];
$filestoconvert_succeed_blocks = [];

if (isset($data_unserialized->filestoconvert_succeed)) {
    foreach($data_unserialized->filestoconvert_succeed as $file) {
        if ($file->sectionnumber) { // modules
            if (!isset($filestoconvert_succeed_mods[$file->sectionnumber])) {
                $sectionInfo = new stdClass();
                $sectionInfo->number = $file->sectionnumber;
                $sectionInfo->name =  (empty($file->sectionname) ? 'Section '.$file->sectionnumber : $file->sectionname);
                $sectionInfo->url = course_get_url($course, $file->sectionnumber);
                $sectionInfo->mods = array();

                $filestoconvert_succeed_mods[$file->sectionnumber] = $sectionInfo;
            }

            if ($file->oid) {
                if (!isset($filestoconvert_succeed_mods[$file->sectionnumber]->mods[$file->oid])) {
                    $moduleInfo = new stdClass();
                    $moduleInfo->cmid = $file->oid;
                    $moduleInfo->type = $file->otype;
                    $moduleInfo->url = new moodle_url($CFG->wwwroot.'/mod/'.$file->otype.'/view.php', array('id'=>$file->oid));  //$CFG->wwwroot.'/mod/'.$file->otype.'/view.php?id='.$file->oid;
                    $moduleInfo->files = array();

                    $filestoconvert_succeed_mods[$file->sectionnumber]->mods[$file->oid] = $moduleInfo;
                }
                if (isset($file->resourceid)) {
                    $file->crlink = CourseFilesOptimizer::RC_TAG_BEGIN.$file->resourceid.CourseFilesOptimizer::RC_TAG_END;
                }
                $filestoconvert_succeed_mods[$file->sectionnumber]->mods[$file->oid]->files[] = $file;

                $totalFilesSize += $file->filesize;
                
            }
        } else { // bloc files
            if ($file->oid) {
                if (!isset($filestoconvert_succeed_blocks[$file->oid])) {
                    $blockInfo = new stdClass();
                    $blockInfo->cmid = $file->oid;
                    $blockInfo->type = $file->otype;
                    $blockInfo->files = array();

                    $filestoconvert_succeed_blocks[$file->oid] = $blockInfo;
                }
                
                if (isset($file->resourceid)) {
                    $file->crlink = CourseFilesOptimizer::RC_TAG_BEGIN.$file->resourceid.CourseFilesOptimizer::RC_TAG_END;
                }
                $filestoconvert_succeed_blocks[$file->oid]->files[] = $file;

                $totalFilesSize += $file->filesize;
            }
        }
    }
}


// write the HTML
$html = '';

$html .= '<h1>Optimisation du '.date('d/m/Y \à H\hi', $startTime).'</h1>';
$html .= '<h2>'.$course->fullname.' ('.createHtmlLink($courseUrl->out()).')</h2>';


// fichiers inutilisés supprimés
if (isset($data_unserialized->unusedfiles_succeed) && count($unusedfiles_succeed_mods)) {
    $html .= '<h3>Fichiers inutilisés supprimés</h3>';
    foreach($unusedfiles_succeed_mods as $sectionInfo) {
        $html .= '<h4>'.$sectionInfo->name.' ('.createHtmlLink($sectionInfo->url->out()).')</h4>';
        foreach($sectionInfo->mods as $mod) {
            $html .= '<h5>&nbsp;&nbsp;&nbsp;'.$mod->type.' ('.createHtmlLink($mod->url->out()).')</h5>';
            $html .= '<ul>';
            foreach($mod->files as $file) {
                $html .= '<li>'.$file->filename.' - '.getReadableFileSize($file->filesize).'</li>';
            }
            $html .= '</ul>';
        }
    }

    if (count($unusedfiles_succeed_blocks)) {
        $html .= '<h4>Fichiers des blocs</h4>';
    }
    foreach($unusedfiles_succeed_blocks as $block) {
        $html .= '<h5>&nbsp;&nbsp;&nbsp;'.$block->type.'</h5>';
        $html .= '<ul>';
        foreach($block->files as $file) {
            $html .= '<li>'.$file->filename.' - '.getReadableFileSize($file->filesize).'</li>';
        }
        $html .= '</ul>';
    }
}

// fichiers déplacés
if (isset($data_unserialized->filestoconvert_succeed) && count($filestoconvert_succeed_mods)) {
    $html .= '<h3>Fichiers déplacés dans les ressources centralisées</h3>';
    foreach($filestoconvert_succeed_mods as $sectionInfo) {
        $html .= '<h4>'.$sectionInfo->name.' ('.createHtmlLink($sectionInfo->url->out()).')</h4>';
        foreach($sectionInfo->mods as $mod) {
            $html .= '<h5>&nbsp;&nbsp;&nbsp;'.$mod->type.' ('.createHtmlLink($mod->url->out()).')</h5>';
            $html .= '<ul>';
            foreach($mod->files as $file) {
                $html .= '<li>'.$file->filename.' - '.getReadableFileSize($file->filesize).(isset($file->crlink) ? ' - '.$file->crlink : '').'</li>';
            }
            $html .= '</ul>';
        }
    }
    if (count($unusedfiles_succeed_blocks)) {
        $html .= '<h4>Fichiers des blocs</h4>';
    }
    foreach($filestoconvert_succeed_blocks as $block) {
        $html .= '<h5>&nbsp;&nbsp;&nbsp;'.$block->type.'</h5>';
        $html .= '<ul>';
        foreach($block->files as $file) {
            $html .= '<li>'.$file->filename.' - '.getReadableFileSize($file->filesize).(isset($file->crlink) ? ' - '.$file->crlink : '').'</li>';
        }
        $html .= '</ul>';
    }
}

// taille totale
$html .= '<p>Votre action a permis d\'alléger votre parcours de '.getReadableFileSize($totalFilesSize).'</p>';

echo $html;




