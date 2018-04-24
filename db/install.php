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
 * Install function
 *
 * @package    block_my_tasks
 * @copyright  2017 Luiz Guilherme Dall Acqua <luizguilherme@nte.ufsm.br>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
/**
 *  Handles install instances of this block.
 *
 * @return bool
 */
function xmldb_block_my_tasks_install() {
    global $CFG, $DB;
    $obj = new stdClass();
    $obj->name = "";
    $obj->timeclose = "";
    $obj->deadline = "";
    $obj->leftjoin = "";
    $obj->wheremodule = [];

    // Where statement based database type.
    if ($CFG->dbtype == 'pgsql') {
        $obj->wheredb = " AND to_timestamp({$CFG->prefix}user_enrolments.timestart) <= now() ";
        $obj->wheredb .= " AND ({$CFG->prefix}user_enrolments.timeend = 0 ";
        $obj->wheredb .= " OR to_timestamp({$CFG->prefix}user_enrolments.timeend) >= now())";
    } else if ($CFG->dbtype == 'mariadb' or $CFG->dbtype == 'mysql') {
        $obj->wheredb = " AND {$CFG->prefix}user_enrolments.timestart <= now() ";
        $obj->wheredb .= " AND ({$CFG->prefix}user_enrolments.timeend = 0 ";
        $obj->wheredb .= " OR {$CFG->prefix}user_enrolments.timeend >= now())";
    } else {
        return false;
    }
    $modules = "'assign','choice','feedback','forum','lesson','quiz','scorm','data','workshop',";
    $modules .= "'glossary','questionnaire','ouwiki','game','hotpot'";
    $modulelist = $DB->get_fieldset_select("modules", 'name', "name in ($modules)");
    foreach ($modulelist as $modulename) {
        switch ($modulename) {
            case 'assign':
                $obj = set_sql_part($obj, $modulename, "duedate", "cutoffdate");
                break;
            case 'forum':
                $obj = set_sql_part($obj, $modulename, "assesstimefinish");
                break;
            case 'lesson':
                $obj = set_sql_part($obj, $modulename, "deadline");
                break;
            case 'data':
                $obj = set_sql_part($obj, $modulename, "timeavailableto");
                break;
            case 'workshop':
                $obj = set_sql_part($obj, $modulename, "submissionend");
                break;
            case 'glossary':
                $obj = set_sql_part($obj, $modulename, "assesstimefinish");
                break;
            case 'questionnaire':
                $obj = set_sql_part($obj, $modulename, "closedate");
                break;
            case 'ouwiki':
                $obj = set_sql_part($obj, $modulename, "editend");
                break;
            default:
                $obj = set_sql_part($obj, $modulename);
                break;
        }
    }
    $obj->wheremodule = implode(" OR ", $obj->wheremodule);

    // Execute create view.
    $DB->execute("DROP VIEW IF EXISTS {$CFG->prefix}view_activities");
    if ($DB->execute(get_sql($obj))) {
        return true;
    }
    return false;
}

/**
 * Return objct with sql parts
 *
 * @param stdClass $obj
 * @param string $module
 * @param string $timeclose
 * @param boolean|string $deadline
 * @return stdClass $obj
 */
function set_sql_part($obj, $module, $timeclose = 'timeclose', $deadline = false) {
    global $CFG;

    if ($deadline === false) {
        $deadline = $timeclose;
    }
    $prefix = $CFG->prefix . $module . ".";
    $obj->name .= " WHEN {$prefix}name  IS NOT NULL THEN {$prefix}name  ";
    $obj->timeclose .= " WHEN {$prefix}{$timeclose}  IS NOT NULL THEN {$prefix}{$timeclose} ";
    $obj->deadline .= " WHEN {$prefix}{$deadline}  IS NOT NULL THEN {$prefix}{$deadline} ";
    $obj->leftjoin .= " LEFT JOIN {$CFG->prefix}$module ON (
        {$CFG->prefix}$module.course = {$CFG->prefix}course_modules.course
        AND {$CFG->prefix}$module.id = {$CFG->prefix}course_modules.instance
        AND  {$CFG->prefix}modules.name = '$module')";
    $obj->wheremodule[] = " {$CFG->prefix}modules.name = '$module' ";
    return $obj;
}

/**
 * Return SQL
 * @param stdClass $sqlpart
 * @return string
 */
function get_sql(stdClass $sqlpart) {
    global $CFG;
    return "CREATE OR REPLACE VIEW {$CFG->prefix}view_activities AS
      SELECT
        {$CFG->prefix}course_modules.id       AS activity_id,
        {$CFG->prefix}course_modules.course   AS course_id,
        {$CFG->prefix}course.fullname         AS course_name,
        {$CFG->prefix}course.shortname        AS course_shortname,
        {$CFG->prefix}course_categories.name  AS category_name,
        {$CFG->prefix}course_categories.id    AS category_id,
        {$CFG->prefix}user.id                 AS user_id,
        {$CFG->prefix}role_assignments.roleid AS role_id,
        {$CFG->prefix}modules.name            AS type,
        CASE {$sqlpart->name}         ELSE NULL END  AS activity_name,
        CASE {$sqlpart->timeclose}    ELSE NULL END AS timeclose,
        CASE {$sqlpart->deadline}     ELSE NULL END AS deadline
      FROM {$CFG->prefix}course_modules
        INNER JOIN {$CFG->prefix}course            ON {$CFG->prefix}course.id               = {$CFG->prefix}course_modules.course
        INNER JOIN {$CFG->prefix}course_categories ON {$CFG->prefix}course_categories.id    = {$CFG->prefix}course.category
        INNER JOIN {$CFG->prefix}modules           ON {$CFG->prefix}course_modules.module   = {$CFG->prefix}modules.id
        INNER JOIN {$CFG->prefix}course_sections   ON {$CFG->prefix}course_sections.id      = {$CFG->prefix}course_modules.section
        INNER JOIN {$CFG->prefix}enrol             ON {$CFG->prefix}enrol.courseid          = {$CFG->prefix}course_modules.course
        INNER JOIN {$CFG->prefix}user_enrolments   ON {$CFG->prefix}user_enrolments.enrolid = {$CFG->prefix}enrol.id
        INNER JOIN {$CFG->prefix}user              ON {$CFG->prefix}user.id                 = {$CFG->prefix}user_enrolments.userid
        LEFT JOIN {$CFG->prefix}context ON (
          {$CFG->prefix}context.instanceid = {$CFG->prefix}course.id
          AND {$CFG->prefix}context.contextlevel = 50
        )
        LEFT JOIN {$CFG->prefix}role_assignments ON (
          {$CFG->prefix}role_assignments.contextid = {$CFG->prefix}context.id
          AND {$CFG->prefix}role_assignments.userid = {$CFG->prefix}user.id
        )
        {$sqlpart->leftjoin}
      WHERE
        {$CFG->prefix}course.visible = 1  AND {$CFG->prefix}course_modules.visible = 1 AND {$CFG->prefix}course_sections.visible = 1
        {$sqlpart->wheredb} AND ( {$sqlpart->wheremodule} )";
}

