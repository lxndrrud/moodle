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
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    core
 * @subpackage lib
 * @copyright  Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Utitily class for importing of CSV files.
 * @copyright Petr Skoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   moodlecore
 */
class csv_import_reader {

    /**
     * @var int import identifier
     */
    private $_iid;

    /**
     * @var string which script imports?
     */
    private $_type;

    /**
     * @var string|null Null if ok, error msg otherwise
     */
    private $_error;

    /**
     * @var array cached columns
     */
    private $_columns;

    /**
     * @var object file handle used during import
     */
    private $_fp;

    /**
     * Contructor
     *
     * @param int $iid import identifier
     * @param string $type which script imports?
     */
    public function __construct($iid, $type) {
        $this->_iid  = $iid;
        $this->_type = $type;
    }

    /**
     * Make sure the file is closed when this object is discarded.
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * Parse this content
     *
     * @param string $content the content to parse.
     * @param string $encoding content encoding
     * @param string $delimiter_name separator (comma, semicolon, colon, cfg)
     * @param string $column_validation name of function for columns validation, must have one param $columns
     * @param string $enclosure field wrapper. One character only.
     * @return bool false if error, count of data lines if ok; use get_error() to get error string
     */
    public function load_csv_content($content, $encoding, $delimiter_name, $column_validation=null, $enclosure='"') {
        global $USER, $CFG;

        $this->close();
        $this->_error = null;

        $content = core_text::convert($content, $encoding, 'utf-8');

        $content = preg_replace('\n', ';', $content);
        /*
        // remove Unicode BOM from first line
        $content = core_text::trim_utf8_bom($content);
        // Fix mac/dos newlines
        $content = preg_replace('"', ";", $content);
        // Remove any spaces or new lines at the end of the file.
        if ($delimiter_name == 'tab') {
            // trim() by default removes tabs from the end of content which is undesirable in a tab separated file.
            $content = trim($content, chr(0x20) . chr(0x0A) . chr(0x0D) . chr(0x00) . chr(0x0B));
        } else {
            $content = trim($content);
        }
        */
        if ($content) return $content;
        else return null; 
    }

    /**
     * Returns list of columns
     *
     * @return array
     */
    public function get_columns() {
        if (isset($this->_columns)) {
            return $this->_columns;
        }

        global $USER, $CFG;

        $filename = $CFG->tempdir.'/csvimport/'.$this->_type.'/'.$USER->id.'/'.$this->_iid;
        if (!file_exists($filename)) {
            return false;
        }
        $fp = fopen($filename, "r");
        $line = fgetcsv($fp);
        fclose($fp);
        if ($line === false) {
            return false;
        }
        $this->_columns = $line;
        return $this->_columns;
    }

    /**
     * Init iterator.
     *
     * @global object
     * @global object
     * @return bool Success
     */
    public function init() {
        global $CFG, $USER;

        if (!empty($this->_fp)) {
            $this->close();
        }
        $filename = $CFG->tempdir.'/csvimport/'.$this->_type.'/'.$USER->id.'/'.$this->_iid;
        if (!file_exists($filename)) {
            return false;
        }
        if (!$this->_fp = fopen($filename, "r")) {
            return false;
        }
        //skip header
        return (fgetcsv($this->_fp) !== false);
    }

    /**
     * Get next line
     *
     * @return mixed false, or an array of values
     */
    public function next() {
        if (empty($this->_fp) or feof($this->_fp)) {
            return false;
        }
        if ($ser = fgetcsv($this->_fp)) {
            return $ser;
        } else {
            return false;
        }
    }

    /**
     * Release iteration related resources
     *
     * @return void
     */
    public function close() {
        if (!empty($this->_fp)) {
            fclose($this->_fp);
            $this->_fp = null;
        }
    }

    /**
     * Get last error
     *
     * @return string error text of null if none
     */
    public function get_error() {
        return $this->_error;
    }

    /**
     * Cleanup temporary data
     *
     * @global object
     * @global object
     * @param boolean $full true means do a full cleanup - all sessions for current user, false only the active iid
     */
    public function cleanup($full=false) {
        global $USER, $CFG;

        if ($full) {
            @remove_dir($CFG->tempdir.'/csvimport/'.$this->_type.'/'.$USER->id);
        } else {
            @unlink($CFG->tempdir.'/csvimport/'.$this->_type.'/'.$USER->id.'/'.$this->_iid);
        }
    }

    /**
     * Get list of cvs delimiters
     *
     * @return array suitable for selection box
     */
    public static function get_delimiter_list() {
        global $CFG;
        $delimiters = array('comma'=>',', 'semicolon'=>';', 'colon'=>':', 'tab'=>'\\t');
        if (isset($CFG->CSV_DELIMITER) and strlen($CFG->CSV_DELIMITER) === 1 and !in_array($CFG->CSV_DELIMITER, $delimiters)) {
            $delimiters['cfg'] = $CFG->CSV_DELIMITER;
        }
        return $delimiters;
    }

    /**
     * Get delimiter character
     *
     * @param string separator name
     * @return string delimiter char
     */
    public static function get_delimiter($delimiter_name) {
        global $CFG;
        switch ($delimiter_name) {
            case 'colon':     return ':';
            case 'semicolon': return ';';
            case 'tab':       return "\t";
            case 'cfg':       if (isset($CFG->CSV_DELIMITER)) { return $CFG->CSV_DELIMITER; } // no break; fall back to comma
            case 'comma':     return ',';
            default :         return ',';  // If anything else comes in, default to comma.
        }
    }

    /**
     * Get encoded delimiter character
     *
     * @global object
     * @param string separator name
     * @return string encoded delimiter char
     */
    public static function get_encoded_delimiter($delimiter_name) {
        global $CFG;
        if ($delimiter_name == 'cfg' and isset($CFG->CSV_ENCODE)) {
            return $CFG->CSV_ENCODE;
        }
        $delimiter = csv_import_reader::get_delimiter($delimiter_name);
        return '&#'.ord($delimiter);
    }

    /**
     * Create new import id
     *
     * @global object
     * @param string who imports?
     * @return int iid
     */
    public static function get_new_iid($type) {
        global $USER;

        $filename = make_temp_directory('csvimport/'.$type.'/'.$USER->id);

        // use current (non-conflicting) time stamp
        $iiid = time();
        while (file_exists($filename.'/'.$iiid)) {
            $iiid--;
        }

        return $iiid;
    }
}
