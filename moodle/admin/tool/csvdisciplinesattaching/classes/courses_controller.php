<?php

require_once(__DIR__ . '/../../csvcoursesattaching/classes/controller.php');


class CoursesController {
    private function get_role_assignments_for_category($category) {
        global $DB;
        // Получение дисциплин на категорию
        $courses = tool_csvcoursesattaching_controller::get_courses_by_category($category->id);

        
        // Все подписи учительских ролей в категории
        $role_assignments_teachers = array();
        foreach($courses as $course) {
            // Получение контекста дисциплины
            $course_context = $DB->get_record('context', array(
                'instanceid' => $course->id, 
                'contextlevel' => 50
            ));
            // Массив подписей учителей
            $teachers_role_assignment = $DB->get_records('role_assignments', array(
                'roleid' => tool_csvcoursesattaching_controller::normalize_role('teacher'),
                'contextid' => $course_context->id
            ));
            // Массив учитилей с их дисциплиной
            $teachers = array(
                'assignments' => $teachers_role_assignment,
                'course' => $course
            );
            array_push($role_assignments_teachers, $teachers);
        }

        // Контекст категории дисциплин
        $category_context = $DB->get_record('context', array(
            'instanceid' => $category->id, 
            'contextlevel' => 40
        ));

        // Подписи ролей студентов на категорию
        $role_assignments_students = $DB->get_records('role_assignments', array(
            'roleid' => tool_csvcoursesattaching_controller::normalize_role('student'),
            'contextid' => $category_context->id,
        ));

        
        // Результирующий массив со всеми подписями студентов на категорию и учителей на отдельные дисциплины
        return array(
            'students' => $role_assignments_students,
            'teachers' => $role_assignments_teachers
        );
    }

    private function delete_students_from_courses($courses, $students_role_assignments_array) {
        global $DB;
        foreach($students_role_assignments_array as $student_assignment) {
            $DB->delete_records('role_assignments', array(
                'id' => $student_assignment->id,
            ));
            foreach($courses as $course) {
                $enrol = $DB->get_record('enrol', array(
                    'enrol' => 'manual',
                    'courseid' => $course->id
                ));
                $DB->delete_records('user_enrolments', array(
                    'enrolid' => $enrol->id,
                    'userid' => $student_assignment->userid,
                ));
            }
        }
    }

    private function delete_teachers_from_courses($teachers_role_assignments_array) {
        global $DB;
        // Отвязать учителей от их дисциплин в категории
        foreach($teachers_role_assignments_array as $teachers_assignments) {
            // Учителя на одну дисциплину
            foreach($teachers_assignments['assignments'] as $teacher_assignment) {
                $DB->delete_records('role_assignments', array(
                    'id' => $teacher_assignment->id,
                ));
                $enrol = $DB->get_record('enrol', array(
                    'enrol' => 'manual',
                    'courseid' => $teachers_assignments['course']->id
                ));
                $DB->delete_records('user_enrolments', array(
                    'enrolid' => $enrol->id,
                    'userid' => $teacher_assignment->userid,
                ));
            }
        }
    }


    private function delete_users_from_category($category, bool $delete_users_flag) {
        // Получить дисциплины на категорию
        $courses = tool_csvcoursesattaching_controller::get_courses_by_category($category->id);
        // Получить подписи пользователей на дисциплину 
        $role_assignments = $this->get_role_assignments_for_category($category);
        // Отвязать студентов от всех дисциплин в категории
        if ($delete_users_flag) {
            $this->delete_students_from_courses($courses, $role_assignments['students']);
        }
        // Отвязать учителей от их дисциплин в категории
        $this->delete_teachers_from_courses($role_assignments['teachers']);
    }

    private function create_course_in_category(int $category_id, string $course_full_name, string $course_short_name) {
        global $DB;

        $DB->insert_record("course", array(
            "category" => $category_id, 
            "fullname" => $course_full_name,
            "shortname" => $course_short_name,
            "startdate" => strtotime("now"),
            "enddate" => strtotime("+1 year"),
            "timecreated" => strtotime("now"),
            "timemodified" => strtotime("now"),
            "summary" => "",
            "summaryformat" => 1,
            "showactivitydates" => 1,
            "showcompletionconditions" => 1,
            "enablecompletion" => 1
        ));

        $course = $DB->get_record("course", array(
            "category" => $category_id, 
            "fullname" => $course_full_name,
            "shortname" => $course_short_name,
        ));

        // Manual enrol
        $DB->insert_record('enrol', array(
            "enrol" => "manual",
            "status" => 0,
            "courseid" => $course->id,
            "expirythreshold" => 86400,
            "roleid" => 5,
            "timecreated" => strtotime("now"),
            "timemodified" => strtotime("now"),
        ));

        // Guest enrol
        $DB->insert_record('enrol', array(
            "enrol" => "guest",
            "status" => 1,
            "courseid" => $course->id,
            "expirythreshold" => 0,
            'password' => "",
            "roleid" => 0,
            "timecreated" => strtotime("now"),
            "timemodified" => strtotime("now"),
        ));

        // Self enrol 
        $DB->insert_record('enrol', array(
            "enrol" => "self",
            "status" => 1,
            "courseid" => $course->id,
            "expirythreshold" => 86400,
            "roleid" => 5,
            "timecreated" => strtotime("now"),
            "timemodified" => strtotime("now"),
            "customint1" => 0,
            "customint2" => 0,
            "customint3" => 0,
            "customint4" => 1,
            "customint5" => 0,
            "customint6" => 1,
        ));

        $category_context = $DB->get_record('context', array(
            "instanceid" => $category_id,
            "contextlevel" => 40
        ));

        $DB->insert_record('context', array(
            "instanceid" => $course->id,
            "contextlevel" => 50,
            "path" => $category_context->path . "/" . $course->id,
            "depth" => $category_context->depth + 1 
        ));

        return $course->id;
    }

    public function process_csv_content($content) {
        // Split CSV into rows
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
                if (count($row_array) !== 9) 
                    throw new Exception("В строке неверное количество столбцов (не 9)!"); 

                // Информация
                $category_id = (int) $row_array[0];
                $category_short_name = $row_array[1];
                $course_full_name = $row_array[2];
                $course_short_name = $row_array[3];
                $group_name = $row_array[4];
                $teacher_login_1 = $row_array[5];
                $teacher_login_2 = $row_array[6];
                $teacher_login_3 = $row_array[7];
                $delete_users_flag = $row_array[8];

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
                    $category = tool_csvcoursesattaching_controller::get_category_by_short_name($category_short_name);
                }
                else {
                    $category = tool_csvcoursesattaching_controller::get_category_by_id($category_id);
                }
                if (!$category) 
                    throw new Exception("Категория дисциплин не найдена!");

                // Удалить всех пользователей в категории
                if ($delete_users_flag === "+")
                    $this->delete_users_from_category($category, true);
                // ИЛИ только учителей
                else 
                    $this->delete_users_from_category($category, false);

                // Проверить существование определенного курса
                $course_id = 0;
                if ($course_short_name !== "") {
                    $course_by_short = tool_csvcoursesattaching_controller::get_course_by_shortname($course_short_name);
                    if ($course_by_short) $course_id = $course_by_short->id;
                } 
                else {
                    $course_by_full = tool_csvcoursesattaching_controller::get_course_by_fullname($course_full_name);
                    if ($course_by_full) $course_id = $course_by_full->id;
                }
                if ($course_id === 0) {
                    $course_id = $this->create_course_in_category($category_id, $course_full_name, $course_short_name);
                }


                // Найти группу по названию
                $group = tool_csvcoursesattaching_controller::get_group_by_info($group_name);

                if (!$group) 
                    throw new Exception("Группа не найдена!");

                // Подписать группу
                tool_csvcoursesattaching_controller::register_group($group->id, $category->id);

                if ($teacher_login_1 !== "") {
                    $teacher1 = tool_csvcoursesattaching_controller::get_user_by_info("", "", $teacher_login_1);
                    if (!$teacher1) 
                        echo("<p>Строка #" . $counter. "Не найден преподаватель с логином: " . $teacher_login_1 . "</p>");

                    if ($teacher1) 
                        tool_csvcoursesattaching_controller::register_teacher($teacher1->id, $course_id);
                }
                if ($teacher_login_2 !== "") {
                    $teacher2 = tool_csvcoursesattaching_controller::get_user_by_info("", "", $teacher_login_2);
                    if (!$teacher2) 
                        echo("<p>Строка #" . $counter. "Не найден преподаватель с логином: " . $teacher_login_2 . "</p>");

                    if ($teacher2) 
                        tool_csvcoursesattaching_controller::register_teacher($teacher2->id, $course_id);
                }
                if ($teacher_login_3 !== "") {
                    $teacher3 = tool_csvcoursesattaching_controller::get_user_by_info("", "", $teacher_login_3);
                    if (!$teacher3) 
                        echo("<p>Строка #" . $counter. "Не найден преподаватель с логином: " . $teacher_login_3 . "</p>");

                    if ($teacher3) 
                        tool_csvcoursesattaching_controller::register_teacher($teacher3->id, $course_id);
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
                    "; Флаг полной очистки привязок: " . $delete_users_flag
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