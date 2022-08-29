<?php

require_once(__DIR__ . '/provider.php');


class CoursesController {
    private CoursesProvider $provider;

    function __construct(CoursesProvider $provider_instance) {
        $this->provider = $provider_instance;
    }

    /** 
     * Process usecase 1 CSV content to attach groups/users to courses/categories
     * Функционал 1: прикрепить/открепить студента/группу к категории, преподавателя к дисциплине 
     */
    public function process_usecase_1($content) {
        // Split CSV into rows
        $content_array = array_slice(preg_split('/;/', $content), 0, -1);

        $counter = 1;
        $errors = 0;
        $successful = 0;
        foreach($content_array as $row) {
            if($counter == 1) {
                $counter++;
                continue;
            }

            // Split CSV row into fields
            $row_array = preg_split('/@/', $row);

            // Fields from CSV
            $firstname = $row_array[0]; // Имя, чтоб найти пользователя по имени, может быть не указано
            $lastname = $row_array[1]; // Фамилия, чтоб найти пользователя по имени, может быть не указано
            $username = $row_array[2]; // Логин, чтоб найти пользователя по логину
            $group_name = $row_array[3]; // Название группы, чтоб найти группу по названию
            $member_type = $row_array[4]; // Тип прикрепления (преподаватель или группа или студент)
            $course_fullname = $row_array[5]; // Полное название курса
            $course_shortname = $row_array[6]; // Краткое название курса
            $category_id = $row_array[7]; // Идентификатор категории курсов (одна категория на специальность вроде)
            $action_type = $row_array[8]; // Тип действия: + для прикрепления, - для открепления 

            $course_id = 0;
            if ($course_shortname != '')
                $course_id = $this->provider->get_course_by_shortname($course_shortname) -> id;
            if($course_fullname != '' && ($course_id == 0 || $course_id == null))
                $course_id = $this->provider->get_course_by_fullname($course_fullname) -> id;
            
            
            try {
                // В строке должно быть 9 колонок
                if(count($row_array) != 9) throw new Exception('Ошибка с парсингом CSV.');

                // Прикрепление
                if ($action_type === "+") {
                    // Преподаватель
                    if ($member_type == 'П') {
                        $user = $this->provider->get_user_by_info($firstname, $lastname, $username);
                        if ($user && ($course_id != 0 || $course_id != null))
                            $this->provider->register_teacher(
                                $user -> id, 
                                $course_id
                            );
                        else  throw new Exception('Аргументы не найдены');
                    }

                    // Студент
                    if ($member_type == 'С') {
                        $user = $this->provider->get_user_by_info($firstname, $lastname, $username);
                        if ($user && $category_id)
                            $this->provider->register_student(
                                $user -> id, 
                                $category_id
                            );
                        else  throw new Exception('Аргументы не найдены');
                    }

                    // Группа
                    if ($member_type == 'Г') {
                        $group = $this->provider->get_group_by_info($group_name);
                        if ($group && $category_id)
                            $this->provider->register_group(
                                $group -> id,
                                $category_id
                            );
                        else  throw new Exception('Аргументы не найдены');
                    }
                }
                // Открепление
                else if ($action_type === "-") {
                    // Преподаватель
                    if ($member_type == 'П') {
                        $user = $this->provider->get_user_by_info($firstname, $lastname, $username);
                        if ($user && ($course_id != 0 || $course_id != null))
                            $this->provider->unregister_teacher(
                                $user -> id, 
                                $course_id
                            );
                        else  throw new Exception('Аргументы не найдены');
                    }

                    // Студент
                    if ($member_type == 'С') {
                        $user = $this->provider->get_user_by_info($firstname, $lastname, $username);
                        if ($user && $category_id)
                            $this->provider->unregister_student(
                                $user -> id, 
                                $category_id
                            );
                        else  throw new Exception('Аргументы не найдены');
                    }

                    // Группа
                    if ($member_type == 'Г') {
                        // Если удаление без указания группы
                        if (!$group_name) {
                            if (!$category_id) throw new Exception("Категория не указана!");
                            $category = $this->provider->get_category_by_id($category_id);
                            if (!$category) throw new Exception("Категория не найдена!");
                            $this->provider->delete_students_from_courses($category);
                        // Иначе удаление с указанием группы
                        } else {
                            $group = $this->provider->get_group_by_info($group_name);
                            if ($group && $category_id)
                                $this->provider->unregister_group(
                                    $group -> id,
                                    $category_id
                                );
                            else  throw new Exception('Аргументы не найдены');
                        }
                    }
                }

                $successful++;
                
            } catch (Exception $e) {
                $errors++;
                echo("<p>" . '<strong style="color:red">Ошибка в строке</strong>: ' . $counter ."</p>");
            } finally {
                // Отобразить пропаршенные данные для пользователя в любом случае
                echo(
                    "<p>" .
                    "<strong>Строка</strong>: " . $counter .
                    "; <strong>Имя</strong>: " . $firstname . 
                    "; <strong>Фамилия</strong>: " . $lastname . 
                    "; <strong>Логин</strong>: " . $username . 
                    "; <strong>Группа</strong>: " . $group_name . 
                    "; <strong>Тип</strong>: " . $member_type .
                    "; <strong>Полное название курса</strong>: " . $course_fullname .
                    "; <strong>Краткое название курса</strong>: " . $course_shortname .
                    "; <strong>Номер категории</strong>: " . $category_id .
                    "; <strong>Действие</strong>: " . $action_type .
                    "</p>"
                );
            }
            $counter++;
        }
        return array(
            'successful' => $successful,
            'errors' => $errors
        );
    }

    /** 
     * Process usecase 2
     * Функционал 2 
     */
    public function process_usecase_2($content) {
        // Разбить CSV-файл на строки
        $content_array = array_slice(preg_split('/;/', $content), 0, -1);

        $counter = 1;
        $errors = 0;
        $successful = 0;
        foreach($content_array as $row) {
            if($counter === 1) {
                $counter++;
                continue;
            }

            
            // Split CSV row into fields
            $row_array = preg_split('/@/', $row);

            try {
                if (count($row_array) !== 10) 
                    throw new Exception("В строке неверное количество столбцов (не 10)!"); 

                // Информация
                $category_id = (int) $row_array[0];
                $category_short_name = $row_array[1];
                $course_full_name = $row_array[2];
                $course_short_name = $row_array[3];
                $group_name = $row_array[4];
                $teacher_login_1 = $row_array[5];
                $teacher_login_2 = $row_array[6];
                $teacher_login_3 = $row_array[7];
                $delete_students_flag = $row_array[8];
                $delete_teachers_flag = $row_array[9];


                // Проверить данные
                if ($category_id === "" && $category_short_name === "") 
                    throw new Exception('Идентификатор и краткое название категории не определены!');
                if ($course_full_name === "" && $course_short_name === "")
                    throw new Exception("Краткое и полное название дисциплин не указано!");
                if ($group_name === "")
                    throw new Exception("Название группы не указано!");

                // Получить категорию
                $category = null;
                if ($category_short_name !== '') {
                    $category = $this->provider->get_category_by_short_name($category_short_name);
                }
                else {
                    $category = $this->provider->get_category_by_id($category_id);
                }
                if (!$category) 
                    throw new Exception("Категория дисциплин не найдена!");

                // Удалить всех пользователей в категории
                if ($delete_students_flag === "+")
                    $this->provider->delete_students_from_courses($category);
                // Удалить всех преподавателей в категории
                if ($delete_teachers_flag === "+")
                    $this->provider->delete_teachers_from_courses($category);

                // Проверить существование определенного курса
                $course_id = 0;
                if ($course_short_name !== "") {
                    $course_by_short = $this->provider->get_course_by_shortname($course_short_name);
                    if ($course_by_short) $course_id = $course_by_short->id;
                } 
                else {
                    $course_by_full = $this->provider->get_course_by_fullname($course_full_name);
                    if ($course_by_full) $course_id = $course_by_full->id;
                }
                if ($course_id === 0) {
                    $course_id = $this->provider->create_course_in_category($category_id, $course_full_name, $course_short_name);
                }


                // Найти группу по названию
                $group = $this->provider->get_group_by_info($group_name);
                if (!$group) 
                    throw new Exception("Группа не найдена!");

                // Подписать группу на категорию дисциплин
                $this->provider->register_group($group->id, $category->id);

                // Подписать до трёх преподавателей на дисциплину
                if ($teacher_login_1 !== "") {
                    $teacher1 = $this->provider->get_user_by_info("", "", $teacher_login_1);
                    if (!$teacher1) 
                        echo("<p>Строка #" . $counter. "Не найден преподаватель с логином: " . $teacher_login_1 . "</p>");

                    if ($teacher1) 
                        $this->provider->register_teacher($teacher1->id, $course_id);
                }
                if ($teacher_login_2 !== "") {
                    $teacher2 = $this->provider->get_user_by_info("", "", $teacher_login_2);
                    if (!$teacher2) 
                        echo("<p>Строка #" . $counter. "Не найден преподаватель с логином: " . $teacher_login_2 . "</p>");

                    if ($teacher2) 
                        $this->provider->register_teacher($teacher2->id, $course_id);
                }
                if ($teacher_login_3 !== "") {
                    $teacher3 = $this->provider->get_user_by_info("", "", $teacher_login_3);
                    if (!$teacher3) 
                        echo("<p>Строка #" . $counter. "Не найден преподаватель с логином: " . $teacher_login_3 . "</p>");

                    if ($teacher3) 
                        $this->provider->register_teacher($teacher3->id, $course_id);
                }

                $successful++;
            } catch (Exception $e) {
                $errors++;
                echo("<p>" . '<strong style="color:red">Ошибка в строке</strong>: ' . $counter . "</p><p>". $e ."</p>");
            } finally {
                // Отобразить пропаршенные данные для пользователя в любом случае
                echo(
                    "Строка #" . $counter .
                    "; Номер категории: " . $category_id . 
                    "; Краткое название категории: " . $category_short_name .
                    "; Полное название курса: " . $course_full_name .
                    "; Краткое название курса: " . $course_short_name .
                    "; Название группы: " . $group_name .
                    "; Логин преп. 1: " . $teacher_login_1 .
                    "; Логин преп. 2: " . $teacher_login_2 .
                    "; Логин преп. 3: " . $teacher_login_3 .
                    "; Флаг удаления студентов: " . $delete_students_flag .
                    "; Флаг удаления преподавателей: " . $delete_teachers_flag
                );
                
            }
            $counter++;
        }
        return array(
            'successful' => $successful,
            'errors' => $errors
        );
    }
}