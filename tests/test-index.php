<?php
/**
 * Class formiDableDevChallengePieTest
 *
 * @package FormidableDevChallenege
 */

/**
 * test cases.
 * 1. Is the request run multiple times an hour?
 * 2. Is the table showing the expected results?
 */
class formiDableDevChallengePieTest extends WP_UnitTestCase {

	function setUp() {
        parent::setUp();

        $this->class_instance = new formiDableDevChallengePie();
    }
	function test_request_run(){
		$data = $this->class_instance->pieGetTransient('pFrmLTR');

		$this->assertFalse( get_option( '_transient_timeout_pFrmLTR' ) );
		$now = time();

		$this->assertTrue( set_transient( 'pFrmLTR', $data, 3600 ) );

		$this->assertGreaterThanOrEqual( $now + 3600, get_option( '_transient_timeout_pFrmLTR' ) );
		$this->assertLessThanOrEqual( $now + 3601, get_option( '_transient_timeout_pFrmLTR' ) );

		update_option( '_transient_timeout_pFrmLTR', $now - 1 );
		$this->assertFalse( get_transient( 'pFrmLTR' ) );
	}
	
	function test_pieGetTransient(){
		$transient_data = $this->class_instance->pieGetTransient();
		$expected_data = get_transient( 'pFrmLTR' );
		$this->assertEquals($expected_data, $transient_data);
	}
}
