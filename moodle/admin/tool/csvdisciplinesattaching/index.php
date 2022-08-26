<?php


require_once(__DIR__ . '../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_once(__DIR__ . '/classes/controller.php');
require_once(__DIR__ . '/classes/csv_upload_form.php');
require_once(__DIR__ . '/classes/csv_processor.php');


$returnurl = new moodle_url('/admin/tool/csvdisciplinesattaching/index.php');

$form = new csv_upload_form();
if ($formdata = $form->get_data()) {
    // Использование CSV-reader`а
    $importid = csv_import_reader::get_new_iid('csvdisciplinesattaching');
    $cir = new csv_import_reader($importid, 'csvdisciplinesattaching');

    // Получение содержимого файлов
    $use_case_1_content = $form->get_file_content('coursefile');
    $use_case_2_content = $form->get_file_content('advanced_coursefile');

    // Небольшое форматирование контента 
    $use_case_1_content = preg_replace('/\n/', ";", $use_case_1_content);
    $use_case_2_content = preg_replace('/\n/', ";", $use_case_2_content);

    // Инициализация контроллера
    $controller = new CoursesController(new CoursesProvider());
    

    // Функционал 1
    // Выбор действия + -> прикрепление; - -> открепление;
    // Г - группа; П -> преподаватель; С - студент
    if($use_case_1_content) {
        $array_info = $controller->process_usecase_1($use_case_1_content);
        $message_to_show = 
            '<div style="margin-top: 40px">' .
            '<h2>' . 'Успешно обработанных строк CSV:' . $array_info['successful'] .'</h2>' . 
            '<h2>' . 'Ошибочных строк во время обработки:' . $array_info['errors'] .'</h2>' . 
            '<h2>' . "Успешно обработанные строки занесены в базу данных." . '</h2>' .
            '<h2>' . "Нажмите 'Назад', чтоб вернуться в меню." . '</h2>' .
            '</div>';
        echo($message_to_show);

    // Функционал 2
    // опциональное открепление преп + студ 
    // проверка на существование курса и его создание при его отсутствии в категории
    // привязка студентов на все курсы в категории
    // привязка преподавателей на указанный курс в категории
    } else if($use_case_2_content) {
        $array_info = $controller->process_usecase_2($use_case_2_content);
        $message_to_show = 
            '<div style="margin-top: 40px">' .
            '<h2>' . 'Успешно обработанных строк CSV:' . $array_info['successful'] .'</h2>' . 
            '<h2>' . 'Ошибочных строк во время обработки:' . $array_info['errors'] .'</h2>' . 
            '<h2>' . "Успешно обработанные строки занесены в базу данных." . '</h2>' .
            '<h2>' . "Нажмите 'Назад', чтоб вернуться в меню." . '</h2>' .
            '</div>';
        echo($message_to_show);
    }
    

} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('csvdisciplinesattaching', 'tool_csvdisciplinesattaching'), 'csvdisciplinesattaching', 'tool_csvdisciplinesattaching');
    $form->display();
    echo $OUTPUT->footer();
    die();
}

