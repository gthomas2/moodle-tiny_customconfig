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

namespace tiny_customconfig;

use context;
use editor_tiny\editor;
use editor_tiny\plugin;
use editor_tiny\plugin_with_configuration;
use MongoDB\Exception\Exception;

/**
 * Plugin info
 * @package    tiny_customconfig
 * @author     Guy Thomas <dev@citri.city>
 * @copyright  2023 Citricity Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugininfo extends plugin implements plugin_with_configuration {

    const TOKEN_WWWROOT='~wwwRoot~';
    const TOKEN_THEMEURLS='~themeUrls~';

    protected static function apply_token_wwwroot(string $json): string {
        global $CFG;

        if (strpos($json, self::TOKEN_WWWROOT) === false) {
            return $json;
        }

        return str_replace(self::TOKEN_WWWROOT, $CFG->wwwroot, $json);
    }
    protected static function apply_token_themeurls(string $json): string {
        global $PAGE;

        if (strpos($json, '"' . self::TOKEN_THEMEURLS . '"') === false) {
            return $json;
        }

        $urls = array_map(fn(\moodle_url $url) => $url->out(), $PAGE->theme->css_urls($PAGE));

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            // Recursively replace "~THEMEURLS~" inside the JSON structure
            $decoded = self::replace_themeurls_recursive($decoded, $urls);

            return json_encode($decoded, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $json; // Return the original JSON if there's an error
        }
    }

    protected static function replace_themeurls_recursive($data, array $urls) {
        if (is_array($data)) {
            // Check if it's an associative array (object-like) or a list
            $isAssoc = array_keys($data) !== range(0, count($data) - 1);
            $newData = $isAssoc ? [] : [];

            foreach ($data as $key => $value) {
                if ($value === self::TOKEN_THEMEURLS) {
                    // If it's inside an indexed array, spread URLs
                    if (!$isAssoc) {
                        $newData = array_merge($newData, $urls);
                    } else {
                        // If it's inside an object, replace it directly
                        $newData[$key] = $urls;
                    }
                } else {
                    // Recursively process child elements
                    $newData[$key] = self::replace_themeurls_recursive($value, $urls);
                }
            }
            return $newData;
        }
        return $data;
    }

    protected static function apply_json_str_tokens(string $json): string {
        $class = new \ReflectionClass(__CLASS__);
        $methods = $class->getMethods(\ReflectionMethod::IS_STATIC);

        foreach ($methods as $method) {
            if (str_starts_with($method->name, 'apply_token_')) {
                $json = self::{$method->name}($json);
            }
        }

        return $json;
    }

    /**
     * Hacky way for the configuration amd code to access this config early.
     * Note - I tried to get the config for the editor in configuration.js
     * but couldn't work out how to do it easily.
     *
     * @param string $json
     * @return void
     */
    protected static function add_inline_config(string $json) {
        static $done;
        if ($done) {
            return;
        }
        // Apply tokens and test json valid and abort if invalid.
        try {
            $json = static::apply_json_str_tokens($json);
            $obj = json_decode($json);
        } catch (\Exception) {
            $done = true;
            return;
        }
        if (empty($obj)) {
            $done = true;
            return;
        }
        echo "<script>const tiny_plugin_custom_config = $json;</script>";
        $done = true;
    }
    public static function get_plugin_configuration_for_context(
        context $context,
        array $options,
        array $fpoptions,
        ?editor $editor = null
    ): array {
        $json = get_config('tiny_customconfig', 'json');
        $returnvar = ['json' => []];
        if (!$json) {
            return $returnvar;
        } else {
            try {
                $returnvar['json'] = (array) json_decode($json);
                self::add_inline_config($json);
                return $returnvar;
            } catch (\Exception $e) {
                return $returnvar;
            }
        }
    }
}
