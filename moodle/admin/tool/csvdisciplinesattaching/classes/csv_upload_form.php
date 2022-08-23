<?php

/**
 * File containing the step 1 of the upload form.
 *
 * @package    tool_csvdisciplinesattaching
 * @copyright  2022 lxndrurd
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Upload a file CVS file with course information.
 *
 * @package    tool_csvdisciplinesattaching
 * @copyright  2022 lxndrrud
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class csv_upload_form extends tool_uploadcourse_base_form {

    /**
     * The standard form definiton.
     * @return void
     */
    public function definition () {
        $mform = $this->_form;

        $mform->addElement('header', 'generalhdr', get_string('general'));

        $mform->addElement('filepicker', 'coursefile', get_string('coursefile', 'tool_csvdisciplinesattaching'));
        $mform->addRule('coursefile', null, 'required');
        $mform->addElement(null, 'help-column', get_string('help-column', 'tool_csvdisciplinesattaching'));
        $mform->addElement(null, 'help-row', get_string('help-row', 'tool_csvdisciplinesattaching'));
        $mform->addElement(null, 'help-encoding', get_string('help-encoding', 'tool_csvdisciplinesattaching'));
        $mform->addHelpButton('coursefile', 'coursefile', 'tool_csvdisciplinesattaching');

        $this->add_action_buttons(false, get_string('upload', 'tool_csvdisciplinesattaching'));
    }
}
