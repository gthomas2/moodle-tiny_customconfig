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
 * @package   tiny_customconfig
 * @author    Guy Thomas <dev@citri.city>
 * @copyright 2023 Citricity Ltd
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'Tiny custom config';
$string['config_json'] = 'Custom config JSON (array)';
$string['config_json_desc'] = <<<STR
The configuration JSON that you would like to merge at run time.<br />
NOTE: It should be a valid json object - e.g:<br/>
<pre>
    <code>
        {"content_style": "body { color: red !important; }"}
    </code>
</pre>

You can use a series of tokens to post process your json.
Each token must start and end with the tilde character (~).
Also, you must ensure that your token usage doesn't break JSON.
E.g.

<h3>Bad token usage</h3>
<pre>
    <code>
    {"content_css": ~themeUrls~}
    </code>
</pre>
<h3>Good token usage</h3>
<pre>
    <code>
    {"content_css": "~themeUrls~"}
    </code>
</pre>

<h3>Tokens</h3>

<strong>~themeUrls~</strong>
<p>This token allows you to apply your theme css urls to tinyMce</p>
<p>Example</p>
<pre>
    <code>
    {"content_css": "~themeUrls~"}
    </code>
</pre>

<strong>~wwwRoot~</strong>
<p>This token is replaced with the moodle www root</p>
<p>Example</p>
<pre>
    <code>
    {"content_css": "~wwwRoot~/theme/mytheme/fonts/fonts.css"}
    </code>
</pre>

<strong>~bodyClass~</strong>
<p>This token is replaced with the current pages body classes</p>
<p>Example</p>
<pre>
    <code>
    {"body_class": "~bodyClass~"}
    </code>
</pre>

<strong>Combinations</strong>
<p>Example</p>
<pre>
    <code>
    {
        "content_css": [
            "~themeUrls~",
            "~wwwRoot~/theme/mytheme/fonts/fonts.css"
        ],
        "body_class": "~bodyClass~"
    }
    </code>
</pre>

STR;
