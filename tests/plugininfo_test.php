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

/**
 * PHPUnit tests for the tiny_customconfig plugin.
 *
 * @package    tiny_customconfig
 * @group      tiny_customconfig
 * @author     Guy Thomas
 * @copyright  2025 Citricity Ltd
 * @covers     \tiny_customconfig\plugininfo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugininfo_test extends \advanced_testcase {

    /**
     * Mocks the Moodle $PAGE global object.
     *
     * @param moodle_url[] $cssurls Array of moodle_url objects.
     * @return moodle_page Mocked page object.
     */
    protected function mock_page(array $cssurls): moodle_page {
        $thememock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['css_urls'])
            ->getMock();

        $thememock->method('css_urls')->willReturn($cssurls);

        // Create a mock of moodle_page with a magic getter for theme.
        $page = $this->getMockBuilder(moodle_page::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMock();

        $page->method('__get')->with('theme')->willReturn($thememock);
        return $page;
    }

    /**
     * Returns mock URLs for testing.
     *
     * @return moodle_url[]
     */
    protected function mock_urls(): array {
        return [
            new moodle_url('http://moodle.local/theme/styles1.css'),
            new moodle_url('http://moodle.local/theme/styles2.css'),
        ];
    }

    /**
     * Sets up the global $PAGE mock.
     *
     * @return moodle_page
     */
    protected function apply_default_mock_page(): moodle_page {
        global $PAGE;
        $PAGE = $this->mock_page($this->mock_urls());
        return $PAGE;
    }

    /**
     * PHPUnit setup function.
     */
    protected function setUp(): void {
        $this->apply_default_mock_page();
        $this->resetAfterTest();
    }

    public function test_apply_token_wwwroot(): void {
        global $CFG;
        $CFG->wwwroot = 'http://moodle.local';

        $json = '{"url": "~wwwRoot~/path/to/resource"}';
        $expected = '{"url": "http://moodle.local/path/to/resource"}';

        $result = \phpunit_util::call_internal_method(null, 'apply_token_wwwroot', [$json], plugininfo::class);

        $this->assertSame($expected, $result);
    }

    public function test_apply_token_themeurls(): void {
        $json = '{"content_css": ["~themeUrls~"]}';
        $urls = array_map(fn($url) => $url->out(), $this->mock_urls());
        $expected = json_encode(['content_css' => $urls]);

        $result = \phpunit_util::call_internal_method(null, 'apply_token_themeurls', [$json], plugininfo::class);

        $this->assertSame(json_decode($expected, true), json_decode($result, true));
    }

    public function test_apply_token_themeurls_with_other_entries(): void {
        $json = '{"content_css": ["~themeUrls~", "extra_style.css"]}';
        $urls = array_map(fn($url) => $url->out(), $this->mock_urls());
        array_unshift($urls, 'extra_style.css'); // Ensure extra_style.css remains first.
        $expected = json_encode(['content_css' => $urls]);

        $result = \phpunit_util::call_internal_method(null, 'apply_token_themeurls', [$json], plugininfo::class);

        $this->assertSame(json_decode($expected, true), json_decode($result, true));
    }

    public function test_replace_themeurls_recursive(): void {
        $urls = [
            'http://moodle.local/theme/styles1.css',
            'http://moodle.local/theme/styles2.css',
        ];

        $input = ['content_css' => ["~themeUrls~", "custom_style.css"]];
        $expected = ['content_css' => array_merge($urls, ['custom_style.css'])];

        $result = \phpunit_util::call_internal_method(null, 'replace_themeurls_recursive', [$input, $urls], plugininfo::class);

        $this->assertEqualsCanonicalizing($expected, $result);
    }

    public function test_apply_json_str_tokens(): void {
        global $CFG;
        $origcfg = clone $CFG;

        // Set up mock config.
        $CFG->wwwroot = 'http://moodle.local';

        $json = '{
            "url": "~wwwRoot~/some/path",
            "content_css": ["~themeUrls~"]
        }';

        $expected = json_encode([
            "url" => "http://moodle.local/some/path",
            "content_css" => array_map(fn($url) => $url->out(), $this->mock_urls()),
        ]);

        $result = \phpunit_util::call_internal_method(null, 'apply_json_str_tokens', [$json], plugininfo::class);

        $this->assertSame(json_decode($expected, true), json_decode($result, true));

        // Restore config.
        $CFG = $origcfg;
    }

    public function test_invalid_json_handling(): void {
        $json = '{invalid_json}';

        $result = \phpunit_util::call_internal_method(null, 'apply_token_themeurls', [$json], plugininfo::class);

        $this->assertSame($json, $result);
    }

    public function test_add_inline_config(): void {
        ob_start();
        \phpunit_util::call_internal_method(null, 'add_inline_config', ['{"key": "value"}'], plugininfo::class);
        $output = ob_get_clean();

        $expected = '<script>const tiny_plugin_custom_config = {"key": "value"};</script>';
        $this->assertSame($expected, $output);
    }

    public function test_get_plugin_configuration_for_context_with_valid_json(): void {
        set_config('json', '{"key": "value"}', 'tiny_customconfig');

        $context = context_system::instance();

        $result = plugininfo::get_plugin_configuration_for_context($context, [], []);
        $this->assertSame(['json' => ['key' => 'value']], $result);
    }

    public function test_get_plugin_configuration_for_context_with_invalid_json(): void {
        set_config('json', '{invalid_json}', 'tiny_customconfig');

        $context = context_system::instance();

        $result = plugininfo::get_plugin_configuration_for_context($context, [], []);
        $this->assertSame(['json' => []], $result);
    }
}
