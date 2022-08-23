<?php

require_once(__DIR__ . '../../csvcoursesattaching/classes/controller.php');


class tool_csvdisciplinesattaching_controller {
    private function attach_user_to_course(int $user_id, int $course_id, int $role_id) {

    }

    private function get_role_assignments_for_category($category) {
        global $DB;
        // Получение дисциплин на категорию
        $courses = $DB->get_records('course', array('category' => $category->id));
        
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
                'role_id' => tool_csvcoursesattaching_controller::normalize_role('teacher'),
                'context' => $course_context->id
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
            'role_id' => tool_csvcoursesattaching_controller::normalize_role('student'),
            'context' => $category_context->id,
        ));

        
        // Результирующий массив со всеми подписями студентов на категорию и учителей на отдельные дисциплины
        return array(
            'students' => $role_assignments_students,
            'teachers' => $role_assignments_teachers
        );
    }

    private function delete_users_from_category(int $category_id, string $category_short_name) {
        global $DB;
        // Получить категорию
        $category = null;
        if ($category_short_name !== '') $category = $category = tool_csvcoursesattaching_controller::get_category_by_short_name($category_short_name);
        else $category = tool_csvcoursesattaching_controller::get_category_by_id($category_id);
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

    public function process_csv_content($content) {
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

            $category_id = $row_array[0];
            $category_short_name = $row_array[1];
            $course_full_name = $row_array[2];
            $course_short_name = $row_array[3];
            $group_name = $row_array[4];
            $teacher_login_1 = $row_array[5];
            $teacher_login_2 = $row_array[6];
            $teacher_login_3 = $row_array[7];

            try {
                if (!$category_id && !$category_short_name) 
                    throw new Exception('Идентификатор и краткое название категории не определены!');
                $this->delete_users_from_category($category_id, $category_short_name);
                $successful++;
            }catch (Exception $e) {
                $errors++;
                echo("<p>" . '<strong style="color:red">Ошибка в строке</strong>: ' . $counter . " ". $e ."</p>");
            } finally {
                // Отобразить пропаршенные данные для пользователя в любом случае
                echo(
                    "kek"
                );
            }
        }
    }


}