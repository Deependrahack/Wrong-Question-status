<?php

// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Block block_quiz_participation is defined here.
 *
 * @package     block_quiz_participation
 * @copyright   2022 Deependra Kumar Singh <deepcs20@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once '../../config.php';
require_once 'lib.php';
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);
$cid = optional_param('cid', 0, PARAM_INT);
global $CFG, $DB, $USER, $OUTPUT, $PAGE;
// Page configurations.
$PAGE->set_context(CONTEXT_COURSE::instance($cid));
$PAGE->set_title(get_string('alllearners', 'block_question_status'));
$PAGE->set_heading(get_string('alllearners', 'block_question_status'));
$url = new moodle_url('/blocks/question_status/allusers.php');
$PAGE->set_url($url);

$PAGE->set_pagelayout('standard');
require_login();
$course = $DB->get_record('course', array('id' => $cid));
$courseurl = new \moodle_url("/course/view.php", array('id' => $cid));
$PAGE->set_course($course);
$PAGE->navbar->add($course->fullname, $courseurl);
$PAGE->navbar->add('allusers', $url);
$PAGE->set_title("Quiz users");

echo $OUTPUT->header();
$url_params = array();
$context = CONTEXT_COURSE::instance($cid);
$quizmodule = $DB->get_record('modules', ['name' => 'quiz']);
if (empty($quizmodule)) {
//    return $this->content;
}

$quizids = [];
foreach (get_fast_modinfo($cid)->cms as $cm) {
    if ($cm->module === $quizmodule->id && $cm->uservisible === true) {
        $quizids[] = $cm->instance;
    }
}
$output = '';
$enrolledusers = get_role_users(5, $context, false, '*,u.id', "u.id ASC", true, '', $page * $perpage, $perpage);
$countenrolledusers = get_role_users(5, $context, false, '*,u.id', "u.id ASC", true, '', '', '');
$output = "";
if (!empty($enrolledusers)) {
    $i = 0;
    $learners = array();

    foreach ($enrolledusers as $key => $user) {
        $wrongquestions = get_wrong_questionids_for_user($quizids, $user->id);
        if (empty($wrongquestions)) {
            continue;
        }
        $questionwronghtml = get_quiz_wrongquestion($wrongquestions);
        $userpic = new \user_picture($user);
        $imgurl = $userpic->get_url($PAGE);
        $learners['userlists'][$i]['name'] = $user->firstname . ' ' . $user->lastname;
        $learners['userlists'][$i]['email'] = $user->email;
        $learners['userlists'][$i]['quizcount'] = $questionwronghtml;
        $i++;
    }
    if (!empty($learners)) {
        $learners['headerdisplay'] = true;
        $output .= $OUTPUT->render_from_template('block_question_status/userlist', $learners);
        $url_params = array("cid" => $cid);
            $url = new moodle_url('/blocks/question_status/allusers.php', $url_params);
            $output .= html_writer::start_div('pagination-nav-filter');
            $output .= $OUTPUT->paging_bar(count($countenrolledusers), $page, $perpage, $url);
            $output .= html_writer::end_div();
            $output .= html_writer::end_div();
    } else {
        $output = html_writer::div(get_string('nothingtodisplay', 'block_question_status'), 'alert alert-info mt-3');
    }
}
echo $output;
//echo "</div>";
echo $OUTPUT->footer();
