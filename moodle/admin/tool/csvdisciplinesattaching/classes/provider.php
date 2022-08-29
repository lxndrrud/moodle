<?php
class CoursesProvider {
    /*
    @param string role expected values 'teacher', 'student' 
    */
    public function normalize_role(string $role) {
        if($role == 'teacher') return 3;
        else if($role == 'student') return 5;
    }

    /*
    Find group by info about group
    */
    public function get_group_by_info(string $name) {
        global $DB;

        $group = $DB -> get_record('cohort', array(
            'name' => $name
        ));

        return $group;
    }

    /*
    Find user by info about user
    */
    public function get_user_by_info(string $firstname, string $lastname, string $username) {
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
    public function get_course_by_fullname(string $fullname) {
        global $DB;
        
        $course = $DB -> get_record('course', array(
            'fullname' => $fullname
        ));

        return $course;
    }

    /*
    Find course by shortname
    */
    public function get_course_by_shortname(string $shortname) {
        global $DB;
        
        $course = $DB -> get_record('course', array(
            'shortname' => $shortname
        ));

        return $course;
    }

    /*
    Find category by it`s short name
    */
    public function get_category_by_short_name(string $category_short_name) {
        global $DB;

        $category = $DB -> get_record('course_categories', array(
            'idnumber' => $category_short_name
        ));

        return $category;
    }

    /*
    Find category by id
    */
    public function get_category_by_id(int $category_id) {
        global $DB;

        $category = $DB -> get_record('course_categories', array(
            'id' => $category_id
        ));

        return $category;
    }


    /*
    Find courses by category
    */
    public function get_courses_by_category(int $category_id) {
        global $DB;

        $courses = $DB->get_records('course', array(
            'category' => $category_id
        ));

        return $courses;
    }

    /*
    Register teacher on single course
    */
    public function register_teacher(int $user_id, int $course_id) {
        global $DB;
        $roleid = $this->normalize_role('teacher');
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
        }
        else {
            $DB->update_record('role_assignments', array(
                'id' => $role_assignment_check->id,
                'timemodified' => time(),
            ));
    
        }

        $enrol = $DB->get_record('enrol', array(
            'enrol' => 'manual',
            'courseid' => $course->id
        ));
        $user_enrolment = $DB->get_record('user_enrolments', array(
            'enrolid' => $enrol->id,
            'userid' => $user_id,
        ));
        if (!$user_enrolment) {
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
        } else {
            $DB->update_record('user_enrolments', array(
                'id' => $user_enrolment->id,
                'status' => 0,
                'timemodified' => time()
            ));
        }
    }

    /*
    Unregister teacher on single course
    */
    public function unregister_teacher(int $user_id, int $course_id) {
        global $DB;
        $roleid = $this->normalize_role('teacher');
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
    }

    /**  
    * Register student on category of courses
    */
    public function register_student(int $user_id, int $category_id) {
        global $DB;
        $roleid = $this->normalize_role('student');
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
        if(!$role_assignment_check) {
            $DB->insert_record('role_assignments', array(
                'roleid' => $roleid, 
                'userid' => $user_id,
                'contextid' => $context->id,
                'timemodified' => time(),
                // admin user
                'modifierid' => 2
            ));
        }
        else {
            $DB->update_record('role_assignments', array(
                'id' => $role_assignment_check->id,
                'timemodified' => time(),
            ));
        }

        foreach($courses as $course) {
            $enrol = $DB->get_record('enrol', array(
                'enrol' => 'manual',
                'courseid' => $course->id
            ));
            // Проверить наличие привязки
            $user_enrolment = $DB->get_record('user_enrolments', array(
                'enrolid' => $enrol->id,
                'userid' => $user_id,
            ));
            if (!$user_enrolment) {
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
            } else {
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
    public function unregister_student(int $user_id, int $category_id) {
        global $DB;
        $roleid = $this->normalize_role('student');
        $category = $DB->get_record('course_categories', array('id' => $category_id));
        // courses for category
        $courses = $DB->get_records('course', array('category' => $category_id));
        // context for category
        $category_context = $DB->get_record('context', array(
            'instanceid' => $category->id, 
            'contextlevel' => 40
        ));
        
        $category_role_assignment_check = $DB->get_record('role_assignments', array(
            'roleid' => $roleid,
            'userid' => $user_id,
            'contextid' => $category_context->id
        ));
        
        // Check existing role assignment row to update or to insert if it doesn`t exist
        if($category_role_assignment_check != null) {
            $DB->delete_records('role_assignments', array(
                'id' => $category_role_assignment_check->id,
            ));
            foreach($courses as $course) {
                $enrol = $DB->get_record('enrol', array(
                    'enrol' => 'manual',
                    'courseid' => $course->id
                ));
                $course_context = $DB->get_record('context', array(
                    'instanceid' => $course->id, 
                    'contextlevel' => 50
                ));
                $course_role_assignment_check = $DB->get_record('role_assignments', array(
                    'roleid' => $roleid,
                    'userid' => $user_id,
                    'contextid' => $course_context->id
                ));
                if ($course_role_assignment_check) 
                    $DB->delete_records('role_assignments', array(
                        'id' => $course_role_assignment_check->id,
                    ));
                $DB->delete_records('user_enrolments', array(
                    'enrolid' => $enrol->id,
                    'userid' => $user_id,
                ));
            }
        }
    }

    public function register_group(int $group_id, int $category_id) {
        global $DB;

        $members = $DB -> get_records('cohort_members', array(
            'cohortid' => $group_id
        ));

        foreach($members as $member) {
            $this->register_student($member -> userid, $category_id);
        }
    }

    public function unregister_group(int $group_id, int $category_id) {
        global $DB;

        $members = $DB -> get_records('cohort_members', array(
            'cohortid' => $group_id
        ));

        foreach($members as $member) {
            $this->unregister_student($member -> userid, $category_id);
        }
    }

    private function get_role_assignments_for_teachers(int $category_id) {
        global $DB;
        // Получение дисциплин на категорию
        $courses = $this->get_courses_by_category($category_id);

        
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
                'roleid' => $this->normalize_role('teacher'),
                'contextid' => $course_context->id
            ));
            // Массив учитилей с их дисциплиной
            $teachers = array(
                'assignments' => $teachers_role_assignment,
                'course' => $course
            );
            array_push($role_assignments_teachers, $teachers);
        }

        return $role_assignments_teachers;
    }

    private function get_role_assignments_for_students(int $category_id) {
        global $DB;

        // Контекст категории дисциплин
        $category_context = $DB->get_record('context', array(
            'instanceid' => $category_id, 
            'contextlevel' => 40
        ));

        // Подписи ролей студентов на категорию
        $role_assignments_students = $DB->get_records('role_assignments', array(
            'roleid' => $this->normalize_role('student'),
            'contextid' => $category_context->id,
        ));

        /*
        TODO: доделать для курсов
        // Получить дисциплины на категорию
        $courses = $this->get_courses_by_category($category_id);

        foreach($courses as $course) {
            // Контекст дисциплины
            $category_context = $DB->get_record('context', array(
                'instanceid' => $course->id, 
                'contextlevel' => 50
            ));
        }
        */



        return $role_assignments_students;
    }

    public function delete_students_from_courses($category) {
        global $DB;

        // Получить дисциплины на категорию
        $courses = $this->get_courses_by_category($category->id);

        // Получить привязки ролей для студентов в категории
        $students_role_assignments_array = $this->get_role_assignments_for_students($category->id);


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

    public function delete_teachers_from_courses($category) {
        global $DB;

        $teachers_role_assignments_array = $this->get_role_assignments_for_teachers($category->id);
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

    public function create_course_in_category(int $category_id, string $course_full_name, string $course_short_name) {
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
}