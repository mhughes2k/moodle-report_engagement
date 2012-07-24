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

/**
 * Libs, public API.
 *
 * @package    report
 * @subpackage analytics
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_analytics_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/analytics:view', $context)) {
        $url = new moodle_url('/report/analytics/index.php', array('id' => $course->id));
        $navigation->add(get_string('pluginname', 'report_analytics'), $url,
                         navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

function report_analytics_get_course_summary($courseid) {
    global $CFG, $DB;

    $risks = array();

    //TODO: We want this to rely on enabled indicators in the course...
    require_once($CFG->libdir.'/pluginlib.php');
    $pluginman = plugin_manager::instance();
    $instances = get_plugin_list('analyticsindicator');
    $weightings = $DB->get_records_menu('report_analytics', array('course' => $courseid), '', 'indicator, weight');
    foreach ($instances as $name => $path) {
        if (file_exists("$path/indicator.class.php")) {
            require_once("$path/indicator.class.php");
            $classname = "indicator_$name";
            $indicator = new $classname($courseid);
            $indicatorrisks = $indicator->get_course_risks($courseid);
            $weight = isset($weightings[$name]) ? $weightings[$name] : 0;
            foreach ($indicatorrisks as $userid => $risk) {
                if (!isset($risks[$userid])) {
                    $risks[$userid] = 0;
                }
                $risks[$userid] += $risk * $weight;
            }
        }
    }
    return $risks;
}

/**
 * report_analytics_get_risk_level
 *
 * @param mixed $risk
 * @access public
 * @return array    array of values for which different risk levels take effect
 */
function report_analytics_get_risk_level($risk) {
    global $DB;
    //TODO: accept some instance of an overall record for the course...
    return $risk == 0 ? 0 : ceil($risk * 100 / 20) - 1;
}

function report_analytics_is_core_indicator($indicator) {
    $core = array('login', 'assessment');
    $core = array_flip($core);
    return isset($core[$indicator]);
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array
 */
function report_analytics_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $array = array(
        '*'                         => get_string('page-x', 'pagetype'),
        'report-*'                  => get_string('page-report-x', 'pagetype'),
        'report-analytics-*'        => get_string('page-report-analytics-x',  'report_analytics'),
        'report-analytics-index'    => get_string('page-report-analytics-index',  'report_analytics'),
        'report-analytics-course'   => get_string('page-report-analytics-user',  'report_analytics'),
        'report-analytics-user'     => get_string('page-report-analytics-user',  'report_analytics'),
    );
    return $array;
}
