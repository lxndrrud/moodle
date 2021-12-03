<?php
/**
 * Link to CSV course upload.
 *
 * @package    tool_registeruser_custom
 * @copyright  2021 lxndrrud
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('courses', new admin_externalpage('toolregisterusercustom',
        get_string('registeruser_custom', 'tool_registeruser_custom'), "$CFG->wwwroot/$CFG->admin/tool/registeruser_custom/index.php"));
}