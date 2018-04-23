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
 * Block to view all pending activities
 *
 * @package    block_my_tasks
 * @category   blocks
 * @copyright  2017 Luiz Guilherme Dall Acqua <luizguilherme@nte.ufsm.br>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_my_tasks extends block_base
{
    /**
     *
     */
    function init()
    {
        $this->title = get_string('pluginname', 'block_my_tasks');
    }

    /**
     * @return array
     */
    function applicable_formats()
    {
        return array('all' => true);
    }

    /**
     *
     */
    function specialization()
    {
        $this->title = get_string('pluginname', 'block_my_tasks');
    }


    /**
     * Gets Javascript that may be required for navigation
     */
    function get_required_javascript()
    {
        parent::get_required_javascript();
        $this->page->requires->js_call_amd('block_my_tasks/mytasks', 'init', [[]]);
    }

    /**
     * @return array|stdObject
     */
    function get_content()
    {
        global $COURSE, $DB, $USER;

        if (isguestuser() or !isloggedin()) {
            return [];
        }
        $rows = '';
        $now = (new DateTime())->getTimestamp();
        $where = " and timeclose >= $now";
        $where2 = " and timeclose <= $now and deadline >= $now";

        //Filter by course
        if ($COURSE->id > 1) {
            $where .= " and course_id = {$COURSE->id} ";
            $where2 .= " and course_id = {$COURSE->id} ";
        }

        @$this->content->text = $this->filterBar() . $this->templateHeader();

        $tasks = array_merge($this->viewQuery($where2, [$USER->id]), $this->viewQuery($where, [$USER->id]));
        foreach ($tasks as $row) {
            $rows .= $this->templateTask($row);
        }
        if ($COURSE->id > 1 and count($tasks) == 0) {
            $rows .= $this->templateNothingTask();
        } else if ($COURSE->id <= 1) {
            $rows .= $this->templateNothingTask();
        }
        $this->content->text .= html_writer::div($rows, '');
        return $this->content;
    }


    /**
     * Return query on apply where statement
     * @param $where
     * @param $param
     * @return array
     */
    private function viewQuery($where, $param)
    {
        GLOBAL $DB;
        $sql = "SELECT *  from {view_activities} where user_id = ? $where order by  timeclose  asc ";
        return $DB->get_records_sql($sql, $param);
    }

    /**
     * Return activity template
     * @param $row
     * @return string
     */
    protected function templateTask($row)
    {
        global $COURSE;

        $now = (new DateTime())->getTimestamp();

        if ($row->type == 'assign' && $row->timeclose > 0 && $now > $row->deadline && $now <= $row->timeclose) {
            $date = $row->timeclose;
            $statustitle = get_string('msgtitleactivityendpendding', 'block_my_tasks');
        } else {
            $date = $row->deadline;
            $statustitle = get_string('msgtitleactivityend', 'block_my_tasks');
        }

        $calendarinstance = \core_calendar\type_factory::get_calendar_instance();
        $date = new DateTime($calendarinstance->timestamp_to_date_string($date, '%Y-%m-%dT%H:%M:00', 99, false, false));

        return html_writer::link(new moodle_url("/mod/{$row->type}/view.php",
            ['id' => $row->activity_id]),
            html_writer::div($this->drawBell($this->defineBellColor($row)), 'block-my-tasks-col-content-1') .
            ($COURSE->id <= 1 ?
                html_writer::div(
                    html_writer::div($row->activity_name, 'block-my-tasks-col-title') .
                    html_writer::div($row->course_name, 'block-my-tasks-col-subtitle'),
                    'block-my-tasks-col-content-2'
                ) :
                html_writer::div(html_writer::div($row->activity_name), 'block-my-tasks-col-content-2')
            ) .
            html_writer::div($date->format('d/m'), 'block-my-tasks-col-content-3'),
            [
                'class' => "block-my-tasks-col-row {$row->course_id}",
                'data-dtfinal' => $date->getTimestamp(),
                'title' => $statustitle . $date->format('d/m/Y H:i'),
                'data-toggle' => "tooltip",
                'data-placement' => "top",
            ]
        );
    }

    /**
     * Return header table
     * @return string
     */
    protected function templateHeader()
    {
        return
            html_writer::div(
                html_writer::div(get_string('titleactivityname', 'block_my_tasks'), 'block-my-tasks-col-content-2')
                . html_writer::div(get_string('titleending', 'block_my_tasks'), 'block-my-tasks-col-content-3'),
                'block-my-tasks-col-header-row  '
            );
    }

    /**
     * Return template nothing task
     * @return string
     */
    protected function templateNothingTask()
    {
        return
            html_writer::link('#',
                html_writer::div(
                    html_writer::div(get_string('msgnothingactivity', 'block_my_tasks')), 'block-my-tasks-col-content-2'), [
                    'class' => 'block-my-tasks-col-rownothing',
                    'title' => get_string('msgnothingactivitytitle', 'block_my_tasks')
                ]
            );
    }

    /**
     * Return filter bar
     * @return string
     */
    protected function filterBar()
    {
        return html_writer::tag('form',
            html_writer::div(html_writer::span(get_string('filterby', 'block_my_tasks'), '')
                . html_writer::select([
                    '*' => get_string('filterall', 'block_my_tasks'),
                    's-0day' => get_string('filtertoday', 'block_my_tasks'),
                    's-1day' => get_string('filtertomorrow', 'block_my_tasks'),
                    's-3day' => get_string('filterending3days', 'block_my_tasks'),
                ], get_string('situation', 'block_my_tasks'), null, ['' => get_string('situation', 'block_my_tasks')], [
                    'data-placeholder' => get_string('situation', 'block_my_tasks'),
                    'class' => 'block-my-tasks-filter-situation block-my-tasks-float-right',
                ])), ['class' => 'block-my-tasks-form-filter']
        );
    }

    /**
     * Define color of bell
     * @param stdClass $task
     * @return string $color
     */
    private function defineBellColor(stdClass $task)
    {
        $now = (new DateTime())->setTime(23, 59, 59)->getTimestamp();
        $nextthreedays = (new DateTime())->modify('+3 day')->setTime(23, 59, 59)->getTimestamp();

        if ($task->timeclose <= $now) {
            $color = 'block-my-tasks-bell-red';
        } elseif ($task->timeclose > $now && $task->timeclose <= $nextthreedays) {
            $color = 'block-my-tasks-bell-yellow';
        } else {
            $color = 'block-my-tasks-bell-blue';
        }
        return $color;
    }

    /**
     * Return svg source of bell
     * @param $class
     * @return string
     */
    private function drawBell($class)
    {
        return "<svg version=\"1.1\" class='block-my-tasks-bell $class' xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 595.3 841.9\" style=\"enable-background:new 0 0 595.3 841.9;\" xml:space=\"preserve\"><g><path d=\"M433.6,585.2h-4.2V486c0-73-59.4-132.4-132.4-132.4c-73,0-132.4,59.4-132.4,132.4v99.3h-4.2c-9,0-16.3,7.3-16.3,16.3 	s7.3,16.3,16.3,16.3h273.3c9,0,16.3-7.3,16.3-16.3S442.6,585.2,433.6,585.2L433.6,585.2z M396.6,585.2H197.3V486 c0-55,44.7-99.7,99.7-99.7s99.7,44.7,99.7,99.7V585.2z M396.6,585.2\"/>	<path d=\"M297.4,313.8h0.2c9-0.2,16.2-7.6,16.1-16.6l-0.9-55.3c-0.2-9-7.4-16.1-16.3-16.1h-0.2c-9,0.2-16.2,7.6-16.1,16.6l0.9,55.3 C281.2,306.7,288.5,313.8,297.4,313.8L297.4,313.8z M297.4,313.8\"/>	<path d=\"M389,346.6c0.1,0.1,0.2,0.1,0.2,0.2c7.8,4.6,17.8,2,22.4-5.8l28.2-47.6c4.6-7.7,2.1-17.6-5.5-22.2 c-0.1-0.1-0.2-0.1-0.2-0.2c-7.8-4.6-17.8-2-22.4,5.8l-28.2,47.6C378.9,332,381.4,342,389,346.6L389,346.6z M389,346.6\"/>	<path d=\"M182.3,341c4.6,7.8,14.6,10.3,22.4,5.8c0.1-0.1,0.2-0.1,0.2-0.2c7.6-4.6,10.1-14.6,5.5-22.2l-28.2-47.6 c-4.6-7.8-14.6-10.3-22.4-5.8c-0.1,0.1-0.2,0.1-0.2,0.2c-7.6,4.6-10.1,14.6-5.5,22.2L182.3,341z M182.3,341\"/></g></svg>";
    }
}
