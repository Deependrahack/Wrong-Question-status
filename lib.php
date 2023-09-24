<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

function block_question_status_course_quizs($courseid) {
    global $DB;
    $quizmodule = $DB->get_record('modules', ['name' => 'quiz']);
    
    if (empty($quizmodule)) {
        return array();
    }
    $quizids = [];
    foreach (get_fast_modinfo($courseid)->cms as $cm) {
        if ($cm->module === $quizmodule->id && $cm->uservisible === true) {
            $quizids[] = $cm->instance;
        }
    }
    return $quizids;
}

/*
 * Find the wrong questions  more than 80%
 */

function get_wrong_questionids_for_user($quizids, $userid) {
    global $DB;
    $questiondata = array();

    if (!empty($quizids)) {
        foreach ($quizids as $qid) {
            $newsql = "SELECT uniqueid FROM {quiz_attempts} WHERE quiz =? AND userid =? ORDER BY sumgrades DESC LIMIT 1";
            $questionusageid = $DB->get_field_sql($newsql, array($qid, $userid));
            $params = array();
            $params[] = $questionusageid;
            $params[] = $userid;
            $customsql = "SELECT 
                            qas.id,
                            qas.fraction,
                            qa.questionid,
                            qa.id AS questionattemptid,
                            qa.questionusageid,
                            qa.slot,
                            qas.userid,
                            qbe.*
                          FROM 
                            {question_attempts} qa 
                            JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id 
                             INNER JOIN {question_versions} qv ON qv.questionid = qa.questionid
                             INNER JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid 
                            AND qas.sequencenumber = (
                              SELECT 
                                MAX(sequencenumber) 
                              FROM 
                                {question_attempt_steps} 
                              WHERE 
                                questionattemptid = qa.id
                            ) 
                          WHERE 
                            qa.questionusageid = ? 
                            AND  qas.userid = ?  AND   qas.fraction < 0.000001  
                          ";
            $questions = $DB->get_records_sql($customsql, $params);
            if ($questions) {
                $questiondata[$qid] = $questions;
            }
        }
    }
    return $questiondata;
}
/*
 * Get Quiz Name and Question id
 */

function get_quiz_wrongquestion($wrongquestions) {
    global $DB;
    $questionids = [];
    foreach ($wrongquestions as $quizid => $questions) {
        $quizname = $DB->get_field('quiz', 'name', array('id' => $quizid));
        $wrongid = [];
        foreach ($questions as $question) {
            if(empty($question->idnumber)){
                continue;
            }
            $wrongid[] = $question->idnumber;
        }
        $wrongidhtml = implode(',', $wrongid);
        $questionids[] = '<a tabindex="0" class="" role="button" data-toggle="popover" data-trigger="focus" title="Wrong Questionids" data-content="' . $wrongidhtml . '">' . $quizname . '</a>';
    }
   
    return implode(',', $questionids);
}
