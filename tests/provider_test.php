<?php
// This file is part of Moodle - http://moodle.org/.
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

namespace tiny_customconfig;

use stdClass;
use moodle_page;
use moodle_url;
use context_system;
use tiny_customconfig\plugininfo;
use tiny_customconfig\privacy;

/**
 * PHPUnit tests for the tiny_customconfig plugin.
 *
 * @package    tiny_customconfig
 * @group      tiny_customconfig
 * @author     Guy Thomas
 * @copyright  2025 Citricity Ltd
 * @covers     \tiny_customconfig\privacy\provider
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider_test extends \advanced_testcase {
    public function test_privacy_provider(): void {
        $this->assertSame('privacy:metadata', privacy\provider::get_reason());
    }
}
