<?php
/**
 * Link to CSV course upload.
 *
 * @package    tool_csvcoursesattaching
 * @copyright  2021 lxndrrud
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('courses', new admin_externalpage('toolcsvcoursesattaching',
        get_string('csvcoursesattaching', 'tool_csvcoursesattaching'), "$CFG->wwwroot/$CFG->admin/tool/csvcoursesattaching/index.php"));
}