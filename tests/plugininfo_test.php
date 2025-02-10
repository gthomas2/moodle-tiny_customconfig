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

use tiny_customconfig\plugininfo;
use core_privacy\local\metadata\null_provider;

/**
 * PHPUnit tests for tiny_customconfig plugin.
 *
 * @package    tiny_customconfig
 * @group      tiny_customconfig
 * @author     Guy Thomas
 * @copyright  2023 Citricity Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tiny_customconfig_plugininfo_test extends advanced_testcase {

    protected function mockPage(array $cssurls): moodle_page {
        $thememock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['css_urls'])
            ->getMock();

        $thememock->method('css_urls')->willReturn($cssurls);

        // Create a mock of moodle_page with a magic getter for theme
        $page = $this->getMockBuilder(moodle_page::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMock();

        $page->method('__get')->with('theme')->willReturn($thememock);
        return $page;
    }

    protected function mockUrls() {
        return [
            new moodle_url('http://moodle.local/theme/styles1.css'),
            new moodle_url('http://moodle.local/theme/styles2.css')
        ];
    }

    protected function applyDefaultMockPage() {
        global $PAGE;
        $PAGE = $this->mockPage($this->mockUrls());
        return $PAGE;
    }

    protected function setUp(): void {
        $this->applyDefaultMockPage();
        $this->resetAfterTest();
    }

    public function test_apply_token_wwwroot() {
        global $CFG;
        $CFG->wwwroot = 'http://moodle.local';
        $json = '{"url": "~wwwRoot~/path/to/resource"}';
        $expected = '{"url": "http://moodle.local/path/to/resource"}';

        $result = \phpunit_util::call_internal_method(null, 'apply_token_wwwroot', [$json], plugininfo::class);

        $this->assertSame($expected, $result);
    }

    public function test_apply_token_themeurls() {
        $json = '{"content_css": ["~themeUrls~"]}';
        $urls = array_map(fn($url) => $url->out(), $this->mockUrls());
        $jsonurls = json_encode($urls);
        $expected = '{"content_css":'.$jsonurls.'}';

        $result = \phpunit_util::call_internal_method(null, 'apply_token_themeurls', [$json], plugininfo::class);

        $this->assertSame($expected, $result);
    }

    public function test_apply_token_themeurls_with_other_entries() {
        $json = '{"content_css": ["~themeUrls~", "extra_style.css"]}';
        $urls = array_map(fn($url) => $url->out(), $this->mockUrls());
        $urls = array_merge(['extra_style.css'], $urls);
        $jsonurls = json_encode($urls);
        $expected = '{"content_css":'.$jsonurls.'}';

        $result = \phpunit_util::call_internal_method(null, 'apply_token_themeurls', [$json], plugininfo::class);

        $this->assertSame($expected, $result);
    }

    public function test_replace_themeurls_recursive() {
        $urls = [
            'http://moodle.local/theme/styles1.css',
            'http://moodle.local/theme/styles2.css'
        ];

        $input = [
            'content_css' => ["~themeUrls~", "custom_style.css"]
        ];
        $expected = [
            'content_css' => ["http://moodle.local/theme/styles1.css", "http://moodle.local/theme/styles2.css", "custom_style.css"]
        ];

        $result = \phpunit_util::call_internal_method(null, 'replace_themeurls_recursive', [$input, $urls], plugininfo::class);

        $this->assertEqualsCanonicalizing($expected, $result);
    }

    public function test_apply_json_str_tokens() {
        global $CFG;

        $origcfg = $CFG;

        // Set up mock config
        $CFG = (object) ['wwwroot' => 'http://moodle.local'];

        $json = '{
            "url": "~wwwRoot~/some/path",
            "content_css": ["~themeUrls~"]
        }';

        $expected = '{
            "url": "http://moodle.local/some/path",
            "content_css": ["http://moodle.local/theme/styles1.css","http://moodle.local/theme/styles2.css"]
        }';

        $result = \phpunit_util::call_internal_method(null, 'apply_json_str_tokens', [$json], plugininfo::class);

        $this->assertSame(json_encode(json_decode($expected)), $result);
        $CFG = $origcfg;
    }

    public function test_invalid_json_handling() {
        $json = '{invalid_json}';

        $result = \phpunit_util::call_internal_method(null, 'apply_token_themeurls', [$json], plugininfo::class);

        $this->assertSame($json, $result);
    }

    public function test_add_inline_config() {
        // Capture the output
        ob_start();
        \phpunit_util::call_internal_method(null, 'add_inline_config', ['{"key": "value"}'], plugininfo::class);
        $output = ob_get_clean();

        $expected = '<script>const tiny_plugin_custom_config = {"key": "value"};</script>';
        $this->assertSame($expected, $output);
    }

    public function test_get_plugin_configuration_for_context_with_valid_json() {
        set_config('json', '{"key": "value"}', 'tiny_customconfig');

        $context = context_system::instance();
        $options = [];
        $fpoptions = [];

        $result = plugininfo::get_plugin_configuration_for_context($context, $options, $fpoptions);
        $this->assertSame(['json' => ['key' => 'value']], $result);
    }

    public function test_get_plugin_configuration_for_context_with_invalid_json() {
        set_config('json', '{invalid_json}', 'tiny_customconfig');

        $context = context_system::instance();
        $options = [];
        $fpoptions = [];

        $result = plugininfo::get_plugin_configuration_for_context($context, $options, $fpoptions);
        $this->assertSame(['json' => []], $result);
    }

    public function test_privacy_provider() {
        $this->assertSame('privacy:metadata', tiny_customconfig\privacy\provider::get_reason());
    }
}
