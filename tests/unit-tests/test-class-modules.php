<?php

class Sensei_Class_Modules_Test extends WP_UnitTestCase {

	private $module_taxonomy;

	private $module_id;

    /**
     * Constructor function
     */
    public function __construct(){
        parent::__construct();
    }


    /**
     * setup function
     * This function sets up the lessons, quizes and their questions. This function runs before
     * every single test in this class
     */
    public function setup(){
		$this->factory = new Sensei_Factory();

		// Use the taxonomy for Modules
		$this->module_taxonomy = Sensei()->modules->taxonomy;

		// Set up a new module to use for some tests
		$new_term = wp_insert_term( 'My New Module', $this->module_taxonomy );
		$this->module_id = $new_term[ 'term_id' ];
    }// end function setup()

	public function teardown() {
		wp_delete_term( $this->module_id, $this->module_taxonomy );
	}

    /**
     * Testing the quiz class to make sure it is loaded
     */
    public function testClassInstance() {

        //test if the global sensei quiz class is loaded
        $this->assertTrue( isset( Sensei()->modules ), 'Sensei Modules class is not loaded' );

    } // end testClassInstance

    /**
     * Testing Sensei_Core_Modules::get_term_author
     */
    public function testGetTermAuthor(){

        // setup assertions
        $test_user_id = wp_create_user( 'teacherGetTermAuthor', 'teacherGetTermAuthor', 'teacherGetTermAuthor@test.com' );

        //insert a general term
        wp_insert_term('Get Started', 'module');
        //insert a term as if from the user
        wp_insert_term('Get Started Today', 'module', array(
            'description'=> 'A yummy apple.',
            'slug' => $test_user_id . '-get-started-today'
        ));

        // does the function exist?
        $this->assertTrue( method_exists( 'Sensei_Core_Modules', 'get_term_authors'), 'The function Sensei_Core_Modules::get_term_author does not exist ');

        // does the taxonomy exist
        $module_taxonomy = get_taxonomy('module');
        $this->assertTrue( $module_taxonomy->public , 'The module taxonomy is not loaded' );

        // does it return empty array id for bogus term nam?
        $term_authors = Sensei_Core_Modules::get_term_authors( 'bogusnonexistan' );
        $this->assertTrue( empty( $term_authors ) , 'The function should return false for an invalid term' );

        //does it return the admin user for a valid term ?
        $admin = get_user_by( 'email', get_bloginfo('admin_email') );
        $term_authors = Sensei_Core_Modules::get_term_authors( 'Get Started' );
        $this->assertTrue( $admin == $term_authors[0] , 'The function should return admin user for normal module term.' );

        // does it return the expected new user for the given term registered with that id in front of the slug?
        $term_authors = Sensei_Core_Modules::get_term_authors( 'Get Started Today' );
        $this->assertTrue( get_userdata( $test_user_id  ) == $term_authors[0], 'The function should admin user for normal module term.' );

        // what about terms with the same name but different slug?
        // It should return 2 authors as we've created 2 with the same name
        // insert a term that is the same as the first one
        wp_insert_term('Get Started', 'module', array(
            'description'=> 'A yummy apple.',
            'slug' => $test_user_id . '-get-started'
        ));
        $term_authors = Sensei_Core_Modules::get_term_authors( 'Get Started' );
        $this->assertTrue( 2 == count( $term_authors ), 'The function should admin user for normal module term.' );

    }

	/**
	 * Testing Sensei_Core_Modules::save_lesson_module
	 */
	public function testSetLessonModuleWithGivenCourse() {

		// Fetch a lesson and course
		$lesson = $this->getLesson();
		$course_id = $this->factory->get_random_course_id();

		/*
		 * When the module belongs to the course, we should be able to set it
		 * on the lesson.
		 */

		// Set the module on the course
		wp_set_object_terms( $course_id, $this->module_id, $this->module_taxonomy );

		Sensei()->modules->set_lesson_module( $lesson->ID, $this->module_id, $course_id );
		$this->assertTrue( has_term( $this->module_id, $this->module_taxonomy, $lesson ) );

		/*
		 * When the module does not belong to the course, we should be unset
		 * the lesson's module.
		 */

		// Remove the module from the course
		wp_delete_object_term_relationships( $course_id, $this->module_taxonomy );

		Sensei()->modules->set_lesson_module( $lesson->ID, $this->module_id, $course_id );
		$this->assertEquals( wp_get_object_terms( $lesson->ID, $this->module_taxonomy ), array() );

	}

	public function testSaveLessonModuleWithoutGivenCourse() {

		// Fetch a lesson and course
		$lesson = $this->getLesson();
		$course_id = $this->factory->get_random_course_id();

		// Set the lesson on the course
		update_post_meta( $lesson->ID, '_lesson_course', $course_id );

		/*
		 * When the module belongs to the course, we should be able to set it
		 * on the lesson.
		 */

		// Set the module on the course
		wp_set_object_terms( $course_id, $this->module_id, Sensei()->modules->taxonomy );

		Sensei()->modules->set_lesson_module( $lesson->ID, $this->module_id );
		$this->assertTrue( has_term( $this->module_id, $this->module_taxonomy, $lesson ) );

		/*
		 * When the module does not belong to the course, we should unset the
		 * lesson's module.
		 */

		wp_delete_object_term_relationships( $course_id, $this->module_taxonomy );

		Sensei()->modules->set_lesson_module( $lesson->ID, $this->module_id );
		$this->assertEquals( wp_get_object_terms( $lesson->ID, $this->module_taxonomy ), array() );
	}

	// Helpers

	protected function getLesson() {
		$lesson_id = $this->factory->get_random_lesson_id();
		return get_post( $lesson_id );
	}

} // end class
