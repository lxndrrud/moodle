<?php


require_once(__DIR__ . '../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_once(__DIR__ . '/classes/controller.php');
require_once(__DIR__ . '/classes/csv_upload_form.php');
require_once(__DIR__ . '/classes/csv_processor.php');



$returnurl = new moodle_url('/admin/tool/csvdisciplinesattaching/index.php');

$form = new csv_upload_form();
if ($formdata = $form->get_data()) {
    $importid = csv_import_reader::get_new_iid('csvdisciplinesattaching');
    $cir = new csv_import_reader($importid, 'csvdisciplinesattaching');
    $content = $form->get_file_content('coursefile');
    #$content = $cir -> load_csv_content($content, 'utf-8', ',');

    $content = preg_replace('/\n/', ";", $content);

    if($content) {
        /*
        echo($content);
        $content = array_slice(preg_split('/;/', $content), 0, -1);
        foreach($content as $row){
            echo('<br>');
            echo($row);
            echo('<br>');
        }
        */
        $array_info = tool_csvdisciplinesattaching_controller::process_data($content);
        if(tool_csvdisciplinesattaching_controller::$is_error == False) {
            $message_to_show = 
            '<h2>' . 'Успешно обработано строк CSV:' .$array_info['successful'] .'</h2>' . 
            '<h2>' . 'Ошибочных строк во время обаботки:' .$array_info['errors'] .'</h2>';
            redirect($returnurl, $message_to_show);

        }
        else{
            $message_to_show = 
            '<h2>' . 'Успешно обработано строк CSV:' .$array_info['successful'] .'</h2>' . 
            '<h2>' . 'Ошибочных строк во время обаботки:' .$array_info['errors'] .'</h2>' .
            '<h2>' . "Успешно обработанные строки занесены в базу данных." .'</h2>' .
            '<h2>' . "Нажмите 'Назад', чтоб вернуться в меню." .'</h2>';
            echo($message_to_show);
            tool_csvdisciplinesattaching_controller::$is_error = False;
        }
        
        #echo('<h2>' . 'Успешно обработано строк CSV:' .$array_info['successful'] .'</h2>');
        #echo('<h2>' . 'Ошибочных строк во время обаботки:' .$array_info['errors'] .'</h2>');
    }
    /*
    if (!$content) {
        print_error('csvfileerror', 'tool_uploadcourse', $returnurl, $cir->get_error());
    } else if ($readcount == 0) {
        print_error('csvemptyfile', 'error', $returnurl, $cir->get_error());
    }
    */

} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('csvdisciplinesattaching', 'tool_csvdisciplinesattaching'), 'csvdisciplinesattaching', 'tool_csvdisciplinesattaching');
    $form->display();
    echo $OUTPUT->footer();
    die();
}


/*

if (!empty($formdata)) {
    // Get options from the first form to pass it onto the second.
    foreach ($formdata->options as $key => $value) {
        $data["options[$key]"] = $value;
    }
    #tool_registeruser_controller::process_data($csv_data);
}

*/
