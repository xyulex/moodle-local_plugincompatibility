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
 *
 * @package     local_plugincompatibility
 * @copyright   2020 Raúl Martínez<raulmartinez911@hotmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function get_installed_plugins($destinationversion) {
    $pluginman = core_plugin_manager::instance();
    $plugininfo = $pluginman->get_plugins();

    $contribs = array();
    foreach ($plugininfo as $plugintype => $pluginnames) {

        foreach ($pluginnames as $pluginname => $pluginfo) {
            if (!$pluginfo->is_standard() && !$pluginfo->is_subplugin()) {
                $contribs[$plugintype][$pluginname] = $pluginname;
            }
        }
    }

    foreach ($contribs as $key => $value) {
        foreach ($value as $item) {
            if ($item !== 'plugincompatibility') {
                $pluginname = $key . "_" . $item;
                $compatible = check_compatible_version($destinationversion, $pluginname);
                $data[] = array($pluginname, $compatible);
            }
        }
    }
    return $data;
}

function check_compatible_version($version, $pluginname) {

    $html = @file_get_contents("https://moodle.org/plugins/pluginversions.php?plugin=$pluginname");

    if (!$html) {
        return html_writer::tag('span', get_string('notfound', 'local_plugincompatibility'), array('style' => 'color:black'));
    }

    if (preg_match('/<span class="moodleversions">Moodle.*' . $version . '.*<\/span>/', $html)) {
        return html_writer::tag('span', get_string('compatible', 'local_plugincompatibility'), array('style' => 'color:green'));
    }
    return html_writer::tag('span', get_string('notcompatible', 'local_plugincompatibility'), array('style' => 'color:red'));

}

function local_plugincompatibility_extend_navigation(global_navigation $nav) {
    global $CFG;
    require_once($CFG->libdir . '/environmentlib.php');

    $current_version = normalize_version($CFG->release);
    $current_version_no_minors = explode(".", $current_version);
    $current_version_no_minors = $current_version_no_minors[0] . "." . $current_version_no_minors[1];

    if (has_capability('moodle/site:config', context_system::instance())) {
        $managementsectionnode = navigation_node::create(get_string('pluginname', 'local_plugincompatibility'),
                new moodle_url('/local/plugincompatibility/index.php?version=' . $current_version_no_minors),
                global_navigation::TYPE_CUSTOM,
                null,
                'local_plugincompatibility_table',
                new pix_icon('t/log', ''));

        $managementsectionnode->showinflatnavigation = true;
        $nav->add_node($managementsectionnode);
    }
}