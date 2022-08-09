<?php
class tool_csvcoursesattaching_controller {
    public static $is_error = False;

    /*
    @param string role expected values 'teacher', 'student' 
    */
    public static function normalize_role(string $role) {
        if($role == 'teacher') return 3;
        else if($role == 'student') return 5;
    }

    /*
    Find group by info about group
    */
    public static function get_group_by_info(string $name) {
        global $DB;

        $group = $DB -> get_record('cohort', array(
            'name' => $name
        ));

        return $group;
    }

    /*
    Find user by info about user
    */
    public static function get_user_by_info(string $firstname, string $lastname, string $username) {
        global $DB;

        $user_array = array();
        if($username != '') $user_array += array('username' => $username); 
        if($firstname != '') $user_array += array('firstname' => $firstname); 
        if($lastname != '') $user_array += array('lastname' => $lastname); 

        $user = $DB -> get_record('user', $user_array);

        return $user;
    }

    /*
    Find course by fullname
    */
    public static function get_course_by_fullname(string $fullname) {
        global $DB;
        
        $course = $DB -> get_record('course', array(
            'fullname' => $fullname
        ));

        return $course;
    }

    /*
    Find course by shortname
    */
    public static function get_course_by_shortname(string $shortname) {
        global $DB;
        
        $course = $DB -> get_record('course', array(
            'shortname' => $shortname
        ));

        return $course;
    }
    
    /*
    Register teacher on single course
    */
    public static function register_teacher(int $user_id, int $course_id) {
        #try{
            global $DB;
            $roleid = tool_csvcoursesattaching_controller::normalize_role('teacher');
            // course
            $course = $DB->get_record('course', array('id' => $course_id));
            // context for course
            $context = $DB->get_record('context', array(
                'instanceid' => $course->id, 
                'contextlevel' => 50
            ));

            $role_assignment_check = $DB->get_record('role_assignments', array(
                'roleid' => $roleid,
                'userid' => $user_id,
                'contextid' => $context->id
            ));

            if($role_assignment_check == null) {
                $DB->insert_record('role_assignments', array(
                    'roleid' => $roleid, 
                    'userid' => $user_id,
                    'contextid' => $context->id,
                    'timemodified' => time(),
                    // admin user
                    'modifierid' => 2
                ));
                $enrol = $DB->get_record('enrol', array(
                    'enrol' => 'manual',
                    'courseid' => $course->id
                ));
                $DB->insert_record('user_enrolments', array(
                    'status' => 0,
                    'enrolid' => $enrol->id,
                    'userid' => $user_id,
                    'timestart' => time(),
                    'timecreated' => time(),
                    'timemodified' => time(),
                    // admin user
                    'modifierid' => 2,
                    'timeend' => 0
                ));
            }
            else {
                $DB->update_record('role_assignments', array(
                    'id' => $role_assignment_check->id,
                    'timemodified' => time(),
                ));
                $enrol = $DB->get_record('enrol', array(
                    'enrol' => 'manual',
                    'courseid' => $course->id
                ));
                $user_enrolment = $DB->get_record('user_enrolments', array(
                    'enrolid' => $enrol->id,
                    'userid' => $user_id,
                ));
                $DB->update_record('user_enrolments', array(
                    'id' => $user_enrolment->id,
                    'status' => 0,
                    'timemodified' => time()
                ));
        
            }
        /*  
        }
        
        catch(Exception $e) {
            echo("Ошибка пользователь: " . $user_id . " курс: " . $course_id . " register_teacher");
        }
        */
    }

    /*
    Unregister teacher on single course
    */
    public static function unregister_teacher(int $user_id, int $course_id) {
        global $DB;
        $roleid = tool_csvcoursesattaching_controller::normalize_role('teacher');
        // course
        $course = $DB->get_record('course', array('id' => $course_id));
        // context for course
        $context = $DB->get_record('context', array(
            'instanceid' => $course->id, 
            'contextlevel' => 50
        ));
        
        $role_assignment_check = $DB->get_record('role_assignments', array(
            'roleid' => $roleid,
            'userid' => $user_id,
            'contextid' => $context->id
        ));
        
        // Check existing role assignment row to update or to insert if it doesn`t exist
        if($role_assignment_check != null) {
            $DB->delete_records('role_assignments', array(
                'id' => $role_assignment_check->id,
            ));
            $enrol = $DB->get_record('enrol', array(
                'enrol' => 'manual',
                'courseid' => $course->id
            ));
            $DB->delete_records('user_enrolments', array(
                'enrolid' => $enrol->id,
                'userid' => $user_id,
            ));
            
        }
    /*
        catch (Exception $e) {
            echo("Ошибка пользователь: " . $user_id . " курс: " . $course_id . " unregister_teacher");
        }
    */
        
    }

    /*
    Register student on category of courses
    */
    public static function register_student(int $user_id, int $category_id) {
        global $DB;
        $roleid = tool_csvcoursesattaching_controller::normalize_role('student');
        $category = $DB->get_record('course_categories', array('id' => $category_id));
        // courses for category
        $courses = $DB->get_records('course', array('category' => $category_id));
        // context for category
        $context = $DB->get_record('context', array(
            'instanceid' => $category->id, 
            'contextlevel' => 40
        ));
        
        $role_assignment_check = $DB->get_record('role_assignments', array(
            'roleid' => $roleid,
            'userid' => $user_id,
            'contextid' => $context->id
        ));
        
        // Check existing role assignment row to update or to insert if it doesn`t exist
        if($role_assignment_check == null) {
            $DB->insert_record('role_assignments', array(
                'roleid' => $roleid, 
                'userid' => $user_id,
                'contextid' => $context->id,
                'timemodified' => time(),
                // admin user
                'modifierid' => 2
            ));
            foreach($courses as $course) {
                $enrol = $DB->get_record('enrol', array(
                    'enrol' => 'manual',
                    'courseid' => $course->id
                ));
                $DB->insert_record('user_enrolments', array(
                    'status' => 0,
                    'enrolid' => $enrol->id,
                    'userid' => $user_id,
                    'timestart' => time(),
                    'timecreated' => time(),
                    'timemodified' => time(),
                    // admin user
                    'modifierid' => 2,
                    'timeend' => 0
                ));
            }
        }
        else {
            $DB->update_record('role_assignments', array(
                'id' => $role_assignment_check->id,
                'timemodified' => time(),
            ));
            foreach($courses as $course) {
                $enrol = $DB->get_record('enrol', array(
                    'enrol' => 'manual',
                    'courseid' => $course->id
                ));
                $user_enrolment = $DB->get_record('user_enrolments', array(
                    'enrolid' => $enrol->id,
                    'userid' => $user_id,
                ));
                $DB->update_record('user_enrolments', array(
                    'id' => $user_enrolment->id,
                    'status' => 0,
                    'timemodified' => time()
                ));
            }
        }
    }
    
    /*
    Unregister student on category of courses
    */
    public static function unregister_student(int $user_id, int $category_id) {
        global $DB;
        $roleid = tool_csvcoursesattaching_controller::normalize_role('student');
        $category = $DB->get_record('course_categories', array('id' => $category_id));
        // courses for category
        $courses = $DB->get_records('course', array('category' => $category_id));
        // context for category
        $context = $DB->get_record('context', array(
            'instanceid' => $category->id, 
            'contextlevel' => 40
        ));
        
        $role_assignment_check = $DB->get_record('role_assignments', array(
            'roleid' => $roleid,
            'userid' => $user_id,
            'contextid' => $context->id
        ));
        
        // Check existing role assignment row to update or to insert if it doesn`t exist
        if($role_assignment_check != null) {
            $DB->delete_records('role_assignments', array(
                'id' => $role_assignment_check->id,
            ));
            foreach($courses as $course) {
                $enrol = $DB->get_record('enrol', array(
                    'enrol' => 'manual',
                    'courseid' => $course->id
                ));
                $DB->delete_records('user_enrolments', array(
                    'enrolid' => $enrol->id,
                    'userid' => $user_id,
                ));
            }
        }
    }

    public static function register_group(int $group_id, int $category_id) {
        global $DB;

        $members = $DB -> get_records('cohort_members', array(
            'cohortid' => $group_id
        ));

        foreach($members as $member) {
            tool_csvcoursesattaching_controller::register_student($member -> userid, $category_id);
        }
    }

    public static function unregister_group(int $group_id, int $category_id) {
        global $DB;

        $members = $DB -> get_records('cohort_members', array(
            'cohortid' => $group_id
        ));

        foreach($members as $member) {
            tool_csvcoursesattaching_controller::unregister_student($member -> userid, $category_id);
        }
    }

    /*
    public static function process_data_old($content) {
        $content_array = array_slice(preg_split('/\s+/', $content), 0, -1);
        $counter = 1;
        foreach($content_array as $row) {
            if($counter == 1) {
                $counter++;
                continue;
            }
            $row_array = preg_split('/,/', $row);

            $user = tool_csvcoursesattaching_controller()
            if ($row_array[0] != '')
                $user_info += array('firstname' => $row_array[0]);
            
            if ($row_array[1] != '')
                $user_info += array('lastname' => $row_array[1]);

            if ($row_array[2] != '')
                $user_info += array('username' => $row_array[2]);

            if($row_array[3] == 'П') 
                tool_csvcoursesattaching_controller::register_teacher(
                    $user_info, 
                    $row_array[4]
                );
            if($row_array[3] == 'С') 
                tool_csvcoursesattaching_controller::register_student(
                    $user_info, 
                    $row_array[5]
                );
            $counter++;
        }
    }
    */

    /*
    Process CSV content to attach groups/users to courses/categories
    */
    public static function process_data($content) {
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
                $course_id = tool_csvcoursesattaching_controller::get_course_by_shortname($course_shortname) -> id;
            if($course_fullname != '' && ($course_id == 0 || $course_id == null))
                $course_id = tool_csvcoursesattaching_controller::get_course_by_fullname($course_fullname) -> id;
            
            
            try {
                // В строке должно быть 9 колонок
                if(count($row_array) != 9) throw new Exception('Ошибка с парсингом CSV.');

                // Прикрепление
                if ($action_type === "+") {
                    // Преподаватель
                    if ($member_type == 'П') {
                        $user = tool_csvcoursesattaching_controller::get_user_by_info($firstname, $lastname, $username);
                        if ($user && ($course_id != 0 || $course_id != null))
                            tool_csvcoursesattaching_controller::register_teacher(
                                $user -> id, 
                                $course_id
                            );
                        else  throw new Exception('Аргументы не найдены');
                    }

                    // Студент
                    if ($member_type == 'С') {
                        $user = tool_csvcoursesattaching_controller::get_user_by_info($firstname, $lastname, $username);
                        if ($user && $category_id)
                            tool_csvcoursesattaching_controller::register_student(
                                $user -> id, 
                                $category_id
                            );
                        else  throw new Exception('Аргументы не найдены');
                    }

                    // Группа
                    if ($member_type == 'Г') {
                        $group = tool_csvcoursesattaching_controller::get_group_by_info($group_name);
                        if ($group && $category_id)
                            tool_csvcoursesattaching_controller::register_group(
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
                        $user = tool_csvcoursesattaching_controller::get_user_by_info($firstname, $lastname, $username);
                        if ($user && ($course_id != 0 || $course_id != null))
                            tool_csvcoursesattaching_controller::unregister_teacher(
                                $user -> id, 
                                $course_id
                            );
                        else  throw new Exception('Аргументы не найдены');
                    }

                    // Студент
                    if ($member_type == 'С') {
                        $user = tool_csvcoursesattaching_controller::get_user_by_info($firstname, $lastname, $username);
                        if ($user && $category_id)
                            tool_csvcoursesattaching_controller::unregister_student(
                                $user -> id, 
                                $category_id
                            );
                        else  throw new Exception('Аргументы не найдены');
                    }

                    // Группа
                    if ($member_type == 'Г') {
                        $group = tool_csvcoursesattaching_controller::get_group_by_info($group_name);
                        if ($group && $category_id)
                            tool_csvcoursesattaching_controller::unregister_group(
                                $group -> id,
                                $category_id
                            );
                        else  throw new Exception('Аргументы не найдены');
                    }
                }

                $successful++;
            } catch (Exception $e) {
                $errors++;
                echo("<p>");
                echo(
                    "Ошибка в строке номер: " . $counter .
                    "; Номер категории: " . $category_id .
                    "; Полное название курса: " . $course_fullname .
                    "; Краткое название курса: " . $course_shortname .
                    "; Логин: " . $username . 
                    "; Имя: " . $firstname . 
                    "; Фамилия: " . $lastname
                 );
                echo("</p>");
                tool_csvcoursesattaching_controller::$is_error = True;
            }
            $counter++;
        }
        return array(
            'successful' => $successful,
            'errors' => $errors
        );
    }
}