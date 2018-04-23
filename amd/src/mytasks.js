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
 * @package    block_my_tasks
 * @category   blocks
 * @copyright  2017 Luiz Guilherme Dall Acqua <luizguilherme@nte.ufsm.br>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['require', 'jquery'],
    function (require, $) {
        return {
            init: function (param) {
                var stakTaks = $('.block-my-tasks-col-row');
                var st = $('.block-my-tasks-col-rownothing');
                var filterInput = $(".block-my-tasks-filter-situation");
                filterInput.change(function () {
                    filter();
                });
                function filter() {
                    var dt = new Date();
                    var filter = getValueSituation();
                    dt.setHours(0, 0, 0, 0);
                    switch (filter) {
                        case '*':
                            stakTaks.slideUp(700).filter(function () {
                                return true;
                            }).slideDown(800);
                            break;
                        case 's-0day':
                            stakTaks.slideUp(700).filter(function () {
                                var dt2 = new Date();
                                dt2.setHours(23, 59, 59, 999);
                                return $(this).data('dtfinal') * 1000 < dt2.getTime();

                            }).slideDown(800);
                            break;
                        case 's-1day':
                            stakTaks.slideUp(700).filter(function () {
                                var dt2 = new Date();
                                dt2.setDate(dt2.getDate() + 1);
                                dt2.setHours(23, 59, 59, 999);
                                return $(this).data('dtfinal') * 1000 > dt.getTime()
                                    && $(this).data('dtfinal') * 1000 < dt2.getTime();

                            }).slideDown(800);
                            break;
                        case 's-3day':
                            stakTaks.slideUp(700).filter(function () {
                                var dt2 = new Date();
                                dt2.setDate(dt2.getDate() + 3);
                                dt2.setHours(23, 59, 59, 999);
                                return $(this).data('dtfinal') * 1000 > dt.getTime()
                                    && $(this).data('dtfinal') * 1000 < dt2.getTime();
                            }).slideDown(800);
                            break;
                        default:
                            if (filter != '')
                                stakTaks.slideUp(700).filter('.' + filter).slideDown(800);
                            break;
                    }
                }

                /**
                 * Extract value from filter-task-situation
                 * @returns {*}
                 */
                function getValueSituation() {
                    var $fs = filterInput.val();
                    $fs = typeof($fs) == 'undefined' || $fs == '' ? '*' : $fs;
                    return $fs;
                }

                /**
                 * Show or Hide message for zero activities
                 */
                function existItens() {
                    setInterval(
                        function () {
                            $('.block-my-tasks-col-row:hidden').length == $('.block-my-tasks-col-row').length ?
                                st.slideDown(800) : st.slideUp(800);
                        }, 100
                    )
                }

                filter();
                existItens();
            }
        }
    });
