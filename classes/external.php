<?php

// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace block_question_status;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_multiple_structure;
use external_single_structure;
use moodle_url;
use context_system;
use context_course;
use html_writer;

class external extends external_api {

    /**
     * defines parameters to be passed in ws request
     */
    public static function get_quiz_wrongquestions_parameters() {
        return new external_function_parameters(
                array(
            'courseid' => new external_value(PARAM_INT, 'Courseid', VALUE_OPTIONAL),
                )
        );
    }

    /**
     * Return the learning path info
     * @return array Learning path array
     */
    public static function get_quiz_wrongquestions($courseid = 0) {
        global $DB, $CFG, $OUTPUT, $PAGE;
        require_once $CFG->dirroot . '/blocks/question_status/lib.php';
        $params = self::validate_parameters(self::get_quiz_wrongquestions_parameters(),
                        array('courseid' => $courseid));
        $perpage = 10;
        $context = \context_system::instance();
        $PAGE->set_context($context);

        $quizmodule = $DB->get_record('modules', ['name' => 'quiz']);
        $quizids = [];
        foreach (get_fast_modinfo($params['courseid'])->cms as $cm) {
            if ($cm->module === $quizmodule->id && $cm->uservisible === true) {
                $quizids[] = $cm->instance;
            }
        }
        if (empty($quizids)) {
            $html = array();
            $html['displayhtml'] = html_writer::div(get_string('nothingtodisplay', 'block_question_status'), 'alert alert-info mt-3');
            return $html;
        }
        $courseid = $params['courseid'];
        $context = CONTEXT_COURSE::instance($courseid);
        $enrolledusers = get_role_users(5, $context, false, '*,u.id', "u.id ASC");
        $today = time();
        $start = (date('w', $today) == 0) ? $today : strtotime('last friday', $today);
        $weekstart = date('Y-m-d', $start);
        $weekenddate = strtotime('next friday', $start);
        //Programs start this week                
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
                $learners['trainers'][$i]['name'] = $user->firstname . ' ' . $user->lastname;
                $learners['trainers'][$i]['imageurl'] = "$imgurl";
                $learners['trainers'][$i]['quizcount'] = $questionwronghtml;
                $i++;
            }
            if (!empty($learners)) {
                $learners['headerdisplay'] = true;
                $out .= $OUTPUT->render_from_template('block_question_status/learners', $learners);
                $url_params = array("cid" => $courseid);
                    $alllearnersurl = new moodle_url('/blocks/question_status/allusers.php', $url_params);
                    $seeall = html_writer::start_div('text-center w-100');
                    $seeall .= html_writer::tag('a', "View all", array("class" => "font-w-600 view-all d-block font-14", 'href' => $alllearnersurl));
                    $out .= $seeall;
            } else {
                $out = html_writer::div(get_string('nothingtodisplay', 'block_question_status'), 'alert alert-info mt-3');
            }
        } else {
            $out .= html_writer::div(get_string('nothingtodisplay', 'block_question_status'), 'alert alert-info mt-3');
        }
        $html = array();
        $html['displayhtml'] = $out;
        return $html;
    }

    /**
     * returns leaders info in json format
     */
    public static function get_quiz_wrongquestions_returns() {
        return $data = new external_single_structure([
            'displayhtml' => new external_value(PARAM_RAW, 'html')
        ]);
    }

}
