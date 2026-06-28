<?php
/**
 * Integration tests for the core loan workflow AJAX handlers.
 *
 * @package AssetLendingManager
 */

require_once __DIR__ . '/support/class-almgr-loan-integration-test-case.php';

/**
 * Test class for loan workflow AJAX handlers.
 */
class LoanWorkflowAjaxTest extends ALMGR_Loan_Integration_Test_Case {

	/**
	 * Test loan request submission persists a pending row.
	 */
	public function test_submit_loan_request_inserts_pending_row() {
		$member_id = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$asset_id  = $this->create_asset();
		$message   = "Need this asset <b>soon</b>.\nThanks.";

		wp_set_current_user( $member_id );

		$response = $this->call_ajax_handler(
			'ajax_submit_loan_request',
			array(
				'nonce'    => wp_create_nonce( 'almgr_loan_request_nonce' ),
				'asset_id' => $asset_id,
				'message'  => $message,
			)
		);

		$this->assertTrue( $response['success'] );
		$this->assertSame( 'Loan request sent successfully!', $response['data']['message'] );
		$this->assertSame( 1, $this->count_pending_requests_for_asset( $asset_id ) );

		$request_row = $this->get_request_row( (int) $response['data']['request_id'] );

		$this->assertNotNull( $request_row );
		$this->assertSame( $asset_id, (int) $request_row->asset_id );
		$this->assertSame( $member_id, (int) $request_row->requester_id );
		$this->assertSame( 0, (int) $request_row->owner_id );
		$this->assertSame( 'pending', $request_row->status );
		$this->assertSame( sanitize_textarea_field( $message ), $request_row->request_message );
	}

	/**
	 * Test loan approval transfers ownership and cancels competing requests.
	 */
	public function test_approve_loan_request_transfers_asset_and_cancels_competing_requests() {
		$approver_id   = $this->create_user_with_role( 'administrator' );
		$requester_one = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$requester_two = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$asset_id      = $this->create_asset();

		$request_id = $this->insert_pending_request( $asset_id, $requester_one, 0, 'First request' );
		$this->insert_pending_request( $asset_id, $requester_two, 0, 'Second request' );

		wp_set_current_user( $approver_id );

		$response = $this->call_ajax_handler(
			'ajax_approve_loan_request',
			array(
				'nonce'      => wp_create_nonce( 'almgr_loan_request_nonce' ),
				'request_id' => $request_id,
				'asset_id'   => $asset_id,
			)
		);

		$this->assertTrue( $response['success'] );
		$this->assertSame( 'on-loan', $this->get_asset_state_slug( $asset_id ) );
		$this->assertSame( $requester_one, (int) get_post_meta( $asset_id, '_almgr_current_owner', true ) );
		$this->assertSame( 0, $this->count_pending_requests_for_asset( $asset_id ) );
		$this->assertNull( $this->get_request_row( $request_id ) );

		$history_rows = $this->get_history_rows_for_asset( $asset_id );
		$statuses     = wp_list_pluck( $history_rows, 'status' );

		$this->assertContains( 'approved', $statuses, 'Approved request should be written to history.' );
		$this->assertContains( 'canceled', $statuses, 'Competing request should be auto-canceled.' );
	}

	/**
	 * Test loan rejection writes history and preserves ownership.
	 */
	public function test_reject_loan_request_writes_history_and_leaves_owner_unchanged() {
		$approver_id  = $this->create_user_with_role( 'administrator' );
		$requester_id = $this->create_user_with_role( ALMGR_MEMBER_ROLE );
		$asset_id     = $this->create_asset();
		$request_id   = $this->insert_pending_request( $asset_id, $requester_id, 0, 'Please approve me' );
		$rejection    = 'Not available this week.';

		wp_set_current_user( $approver_id );

		$response = $this->call_ajax_handler(
			'ajax_reject_loan_request',
			array(
				'nonce'             => wp_create_nonce( 'almgr_loan_request_nonce' ),
				'request_id'        => $request_id,
				'asset_id'          => $asset_id,
				'rejection_message' => $rejection,
			)
		);

		$this->assertTrue( $response['success'] );
		$this->assertSame( 0, $this->count_pending_requests_for_asset( $asset_id ) );
		$this->assertNull( $this->get_request_row( $request_id ) );
		$this->assertSame( 0, (int) get_post_meta( $asset_id, '_almgr_current_owner', true ) );

		$history_rows = $this->get_history_rows_for_asset( $asset_id );

		$this->assertCount( 1, $history_rows );
		$this->assertSame( 'rejected', $history_rows[0]->status );
		$this->assertSame( $rejection, $history_rows[0]->message );
	}
}
