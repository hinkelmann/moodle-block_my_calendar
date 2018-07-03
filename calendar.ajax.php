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


define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/calendar/lib.php');


$courses = optional_param_array('curso', [], PARAM_RAW);
$group = optional_param_array('grupo', [], PARAM_RAW);
$user = optional_param('usuario', '', PARAM_RAW);
$dt1 = optional_param('dt1', '', PARAM_RAW);
$dt2 = optional_param('dt2', '', PARAM_RAW);


$dt1 = $dt1 == '' ? new DateTime('first day of') : new DateTime("@$dt1");
$dt2 = $dt2 == '' ? new DateTime('last day of') : new DateTime("@$dt2");
$dt1 = $dt1->format('Y-m-d');
$dt2 = $dt2->format('Y-m-d');

if (!confirm_sesskey()) {
    throw new moodle_exception('invalidsesskey', 'error');
}
if (!isloggedin()) {
    throw new moodle_exception('notlogged', 'chat');
}
$PAGE->set_url('/blocks/my_calendar/calendar.ajax.php');
echo json_encode(calendar_get_json($courses, $group, $user, $dt1, $dt2));


/**
 * Generates the Array for a events calendar
 *
 * @param array $courses list of course to list events from
 * @param array $groups list of group
 * @param array $users user's info
 * @param int $dt1 the unixtimestamp representing the date we want to view, this is used instead of $calmonth
 * @param int $dt2 the unixtimestamp representing the date we want to view, this is used instead of $calmonth
 *     and $calyear to support multiple calendars
 * @return string $content return html table for mini calendar
 */
function calendar_get_json($courses, $groups, $users, $dt1, $dt2)
{
    $dt1 = new DateTime($dt1);
    $dt1->modify('midnight');
    $dt2 = new DateTime($dt2);
    $dt2->modify('tomorrow')->modify('1 second ago');
    $events = calendar_get_events($dt1->getTimestamp(), $dt2->getTimestamp(), $users, $groups, $courses);
    if (!empty($events)) {
        foreach ($events as $eventid => $event) {
            if (!empty($event->modulename)) {
                $cm = get_coursemodule_from_instance($event->modulename, $event->instance);
                if (!\core_availability\info_module::is_user_visible($cm, 0, false)) {
                    unset($events[$eventid]);
                }
            }
        }
    }


    $x = array_map(function ($e) use (&$courses) {
        $hrefparams = array();
        $ct = \core_calendar\type_factory::get_calendar_instance();
        $dt = $ct->timestamp_to_date_string($e->timestart, '%Y-%m-%dT%H:%M:00', 99, false, false);
        $dt = new DateTime($dt);

        if (!empty($courses)) {
            $courses = array_diff($courses, array(SITEID));
            if (count($courses) == 1) {
                $hrefparams['course'] = reset($courses);
            }
        }
        $hrefparams['view'] = 'day';
        $e->url = calendar_get_link_href(new moodle_url(CALENDAR_URL . 'view.php', $hrefparams), 0, 0, 0, $e->timestart);
        $e->url->set_anchor('event_' . $e->id);
        $e->url = $e->url->out();
        $e->description = preg_replace("/\r|\n/", " ", html_to_text($e->description));
        $e->timestart = $dt->getTimestamp();
        return $e;
    }, $events);

    usort($x, function ($a, $b) {
        if ($a->timestart == $b->timestart) {
            return 0;
        }
        return ($a->timestart < $b->timestart) ? -1 : 1;
    });

    return $x;

}

