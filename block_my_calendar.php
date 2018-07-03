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
 * Moodle's ufsm My Calendar Plugin
 * @package    theme_ufsm
 * @copyright  2017 Luiz Guilherme Dall Acqua <luizguilherme@nte.ufsm.br>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_my_calendar extends block_base
{
    function init()
    {
        $this->title = get_string('pluginname', 'block_my_calendar');
    }

    function applicable_formats()
    {
        return array('all' => true);
    }

    function specialization()
    {
        $this->title = get_string('pluginname', 'block_my_calendar');
    }

    /**
     * Gets Javascript that may be required for navigation
     */
    function get_required_javascript()
    {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/calendar/lib.php');
        $filtercourse = $this->page->course->id == SITEID ?
            calendar_get_default_courses() :
            [$this->page->course->id => $this->page->course];

        list($courses, $group, $user) = calendar_set_filters($filtercourse);

        //var_dump($USER->lang);
        parent::get_required_javascript();
        $this->page->requires->js_call_amd('block_my_calendar/calendar', 'init',
            [[
                'lang' => $USER->lang,
                'timezone'=>'',
                'curso' => $courses,
                'grupo' => $group,
                'usuario' => $user,
                'idcurso' => $this->page->course->id
            ]]
        );
    }

    function get_content()
    {

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';



        $url1 = new moodle_url('/calendar/view.php', [
            'view' => 'upcoming',
            'course' => $this->page->course->id
        ]);

        $url2 = new moodle_url('/calendar/event.php', [
            'action' => 'new',
            'course' => $this->page->course->id
        ]);

        $context = has_any_capability(
            ['moodle/calendar:manageentries', 'moodle/calendar:manageownentries'],
            context_course::instance($this->page->course->id));

        $ferramentas = html_writer::div(
            html_writer::link($url1,
                html_writer::tag('i', '', ['class' => 'fa fa-calendar']),
                ['title' => get_string('gotocalendar', 'calendar')]
            ) . ($context ?
                html_writer::link($url2,
                    html_writer::tag('i', '', ['class' => 'fa fa-plus']),
                    ['title' => get_string('newevent', 'calendar')]
                ) : '')
            , 'icon-action pull-right'
        );


        //    $listaEventos = html_writer::tag('ul', '', ['class' => 'event-render list-unstyled']);

        $dt = new DateTime();
        $painelEventosCabecalho = html_writer::div(
            get_string('eventsofday', 'block_my_calendar')
            . $ferramentas
            , 'panel panel-heading my-calendar-header');
        $painelEventosCorpo = html_writer::div(null, 'event-render');
        $painelEventos = html_writer::div($painelEventosCabecalho . $painelEventosCorpo, 'panel panel-event');

     //   $this->content->text .= html_writer::div($dt->format('H:i:s'), 'row panel panel-body');
        $this->content->text .= html_writer::div('', 'row', ['id' => 'block_my_calendar']);
        $this->content->text .= html_writer::div('', 'clear-fix');
        $this->content->text .= html_writer::div($painelEventos, 'row', ['id' => 'block_my_calendar_events']);
        $this->content->text .= "<style>.panel-event{margin-top:10px} .icon-action.pull-right a { color: #fff;margin-left: 10px;}.event-render a:hover {font-weight: bold;}.event-render a {text-decoration:none;display: block;margin-bottom: 8px;}.event-render {padding: 5px;} </style>";


        return $this->content;
    }

    /**
     * Generates the Array for a events calendar
     *
     * @param array $courses list of course to list events from
     * @param array $groups list of group
     * @param array $users user's info
     * @param int|bool $calmonth calendar month in numeric, default is set to false
     * @param int|bool $calyear calendar month in numeric, default is set to false
     * @param string|bool $placement the place/page the calendar is set to appear - passed on the the controls function
     * @param int|bool $courseid id of the course the calendar is displayed on - passed on the the controls function
     * @param int $dt1 the unixtimestamp representing the date we want to view, this is used instead of $calmonth
     * @param int $dt2 the unixtimestamp representing the date we want to view, this is used instead of $calmonth
     *     and $calyear to support multiple calendars
     * @return string $content return html table for mini calendar
     */
    public static function calendar_get_json($courses, $groups, $users, $calmonth = false, $calyear = false, $placement = false,
                                             $courseid = false, $dt1, $dt2)
    {
        global $CFG;
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
        $events[] = array_map(function ($e) use (&$courses) {
            $hrefparams = array();
            if (!empty($courses)) {
                $courses = array_diff($courses, array(SITEID));
                if (count($courses) == 1) {
                    $hrefparams['course'] = reset($courses);
                }
            }
            $hrefparams['view'] = 'day';
            $e->url = calendar_get_link_href(new moodle_url(CALENDAR_URL . 'view.php', $hrefparams), 0, 0, 0, 1);
            $e->url->set_anchor('event_' . $e->id);
            $e->url = $e->url->out();


            return $e;
        }, $events);
        return $events;
    }

    public function get_events()
    {
        return ['true', 'or', 'false'];
    }
}