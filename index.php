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

include('../../config.php');

defined('MOODLE_INTERNAL') || die;

require_login();

include('lib.php');
require_once($CFG->libdir . '/environmentlib.php');
require_once($CFG->libdir . '/dataformatlib.php');

if (has_capability('moodle/site:config', context_system::instance())) {// Get current Moodle version
    $current_version = $CFG->release;
    $destinationversion = optional_param('version', '', PARAM_TEXT);
    $dataformat = optional_param('dataformat', '', PARAM_ALPHA);
    // Calculate list of versions
    $versions = array();
    if ($contents = load_environment_xml()) {
        if ($env_versions = get_list_of_environment_versions($contents)) {
            // Set the current version at the beginning
            $env_version = normalize_version($current_version); //We need this later (for the upwards)
            $versions[$env_version] = $current_version;
            // If no version has been previously selected, default to $current_version
            if (empty($version)) {
                $version = $env_version;
            }
            //Iterate over each version, adding bigger than current
            foreach ($env_versions as $env_version) {
                if (version_compare(normalize_version($current_version), $env_version, '<')) {
                    $versions[$env_version] = $env_version;
                }
            }
            // Add 'upwards' to the last element
            $versions[$env_version] = $env_version . ' ' . get_string('upwards', 'admin');
        } else {
            $versions = array('error' => get_string('error'));
        }
    }

    $PAGE->requires->css('/styles.css');
    $PAGE->set_url('/local/plugincompatibility/index.php');
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string('pluginname', 'local_plugincompatibility'));
    $PAGE->set_heading(get_string('pluginname', 'local_plugincompatibility'));

    $data = get_installed_plugins($destinationversion);

    $cleaned_data = array();

    if ($dataformat) {
        $fields = array(
                'plugin' => get_string('plugin'),
                'compatibility' => get_string('compatible', 'local_plugincompatibility')
        );

        foreach ($data as $d) {
            $cleaned_data[] = array(
                    $d[0],
                    strip_tags($d[1])
            );
        };

        $filename = clean_filename('compatibility');
        download_as_dataformat($filename, $dataformat, $fields, $cleaned_data);
        exit;
    }

    echo $OUTPUT->header();

    if (empty($data)) { // No plugins installed, just this one.
        echo($OUTPUT->notification(get_string('noinstalledplugins', 'local_plugincompatibility'),
                \core\output\notification::NOTIFY_WARNING));
        echo $OUTPUT->footer();
        return false;
    }

    $output = $OUTPUT->box_start();
    $output .= html_writer::tag('div', get_string('adminhelpenvironment'));
    $select = new single_select(new moodle_url('/local/plugincompatibility/index.php'), 'version', $versions, $version, null);
    $select->label = get_string('moodleversion');
    $output .= $OUTPUT->render($select);
    $output .= $OUTPUT->box_end();
    echo $output;

    $table = new html_table();
    $table->head = array(mb_strtoupper(get_string('name')),
            mb_strtoupper(get_string('dependson', 'local_plugincompatibility')),
            mb_strtoupper(get_string('isitcompatible', 'local_plugincompatibility'))
            )    ;
    $table->size = array('33%', '33%', '33%');
    $table->align = array('center', 'center', 'center');
    $table->data = $data;

    echo html_writer::table($table);
    echo $OUTPUT->download_dataformat_selector(get_string('downloadtable', 'local_plugincompatibility'), null);
    echo $OUTPUT->footer();

}