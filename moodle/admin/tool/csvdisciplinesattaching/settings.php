<?php
/**
 * Link to CSV disciplines upload.
 *
 * @package    tool_csvdisciplinesattaching
 * @copyright  2022 lxndrrud
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('courses', new admin_externalpage('toolcsvdisciplinesattaching',
        get_string('csvdisciplinesattaching', 'tool_csvdisciplinesattaching'), "$CFG->wwwroot/$CFG->admin/tool/csvdisciplinesattaching/index.php"));
}