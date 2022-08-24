<?php

require_once(__DIR__ . '/../../csvcoursesattaching/classes/controller.php');


class CoursesController {
    private function attach_user_to_course(string $login,  string $role, int $course_id, int $category_id) {
        global $DB;
        $user = tool_csvcoursesattaching_controller::get_user_by_info("", "", $login);
        if (!$user)
            throw new Exception("Пользователь не найден: " . $login );

        $normalized_role = tool_csvcoursesattaching_controller::normalize_role($role);
        if (!$normalized_role) 
            throw new Exception("Роль не может быть нормализована: " . $role);

        if ($role === 'teacher') {
            if ($course_id === 0) throw new Exception("Идентификатор категории равен нулю!");
            tool_csvcoursesattaching_controller::register_teacher($user->id, $course_id);
        }
        else if ($role === 'student') {
            if ($category_id === 0) throw new Exception("Идентификатор категории равен нулю!");
            tool_csvcoursesattaching_controller::register_student($user->id, $category_id);
        }
    }

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

    private function delete_users_from_category($category) {
        global $DB;
        
        // Получить дисциплины на категорию
        $courses = tool_csvcoursesattaching_controller::get_courses_by_category($category->id);
        // Получить подписи пользователей на дисциплину 
        $role_assignments = $this->get_role_assignments_for_category($category);
        // отвязать студентов от всех дисциплин в категории
        foreach($role_assignments['students'] as $student_assignment) {
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
        // Отвязать учителей от их дисциплин в категории
        foreach($role_assignments['teachers'] as $teachers_assignments) {
            // Учителия на одну дисциплину
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

    private function create_course_in_category(int $category_id, string $course_full_name, string $course_short_name) {
        global $DB;

        $DB->insert_record("course", array(
            "category" => $category_id, 
            "fullname" => $course_full_name,
            "shortname" => $course_short_name,
            "startdate" => strtotime("now"),
            "enddate" => strtotime("+1 year")
        ));
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
                    $this->delete_users_from_category($category);

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
                    $this->create_course_in_category($category_id, $course_full_name, $course_short_name);
                }


                // Найти группу по названию
                $group = tool_csvcoursesattaching_controller::get_group_by_info($group_name);

                if (!$group) 
                    throw new Exception("Группа не найдена!");

                tool_csvcoursesattaching_controller::register_group($group->id, $category->id);

                if ($teacher_login_1 !== "") {
                    $this->attach_user_to_course($teacher_login_1, 'teacher', $course_id, 0);
                }
                if ($teacher_login_2 !== "") {
                    $this->attach_user_to_course($teacher_login_2, 'teacher', $course_id, 0);
                }
                if ($teacher_login_3 !== "") {
                    $this->attach_user_to_course($teacher_login_3, 'teacher', $course_id, 0);
                }

                $successful++;
            } catch (Exception $e) {
                $errors++;
                echo("<p>" . '<strong style="color:red">Ошибка в строке</strong>: ' . $counter . " ". $e ."</p>");
            } finally {
                // Отобразить пропаршенные данные для пользователя в любом случае
                echo(
                    "id cat: " . $category_id . 
                    "; short name cat: " . $category_short_name .
                    "; full name course: " . $course_full_name .
                    "; short name course: " . $course_short_name .
                    "; group name: " . $group_name .
                    "; delete flag" . $delete_users_flag
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