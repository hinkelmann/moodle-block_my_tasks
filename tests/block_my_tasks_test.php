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
class block_my_tasks_test extends advanced_testcase
{

    /**
     * Dumb tests
     * @return void
     */
    public function test_adding() {
        $this->resetAfterTest(true);
        $this->assertEquals(2, 2);
    }
}
