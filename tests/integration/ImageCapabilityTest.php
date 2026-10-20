<?php
/**
 * Integration tests for the operator image-attachment capability mapping.
 *
 * Operators may edit the metadata of images that belong to an asset (uploaded
 * to an asset, or currently used as an asset's featured image), and nothing else
 * in the Media Library.
 *
 * @package AssetLendingManager
 */

/**
 * Test class for ALMGR_Role_Manager::map_image_attachment_capabilities().
 */
class ImageCapabilityTest extends WP_UnitTestCase {

	/**
	 * Operator user ID.
	 *
	 * @var int
	 */
	private $operator_id;

	/**
	 * Set up roles, the asset post type, and the capability filter.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$role_manager = new ALMGR_Role_Manager();
		$role_manager->activate();
		$role_manager->register();

		( new ALMGR_Asset_Manager() )->register_post_type();

		$this->operator_id = self::factory()->user->create( array( 'role' => ALMGR_OPERATOR_ROLE ) );
	}

	/**
	 * Create an asset post.
	 *
	 * @param string $status Post status.
	 * @return int
	 */
	private function create_asset( string $status = 'publish' ): int {
		return self::factory()->post->create(
			array(
				'post_type'   => ALMGR_ASSET_CPT_SLUG,
				'post_status' => $status,
			)
		);
	}

	/**
	 * Create an attachment.
	 *
	 * @param int    $parent_id Parent post ID.
	 * @param string $mime      Attachment MIME type.
	 * @return int
	 */
	private function create_attachment( int $parent_id = 0, string $mime = 'image/jpeg' ): int {
		return self::factory()->attachment->create_object(
			array(
				'file'           => 'asset-image.jpg',
				'post_parent'    => $parent_id,
				'post_mime_type' => $mime,
			)
		);
	}

	/**
	 * An image used by nothing is out of reach for operators.
	 *
	 * @return void
	 */
	public function test_operator_cannot_edit_an_unrelated_image() {
		$image_id = $this->create_attachment();

		$this->assertFalse( user_can( $this->operator_id, 'edit_post', $image_id ) );
		$this->assertFalse( user_can( $this->operator_id, 'edit_post_meta', $image_id, '_wp_attachment_image_alt' ) );
	}

	/**
	 * An image attached to ordinary content is out of reach as well.
	 *
	 * @return void
	 */
	public function test_operator_cannot_edit_an_image_attached_to_a_regular_post() {
		$post_id  = self::factory()->post->create( array( 'post_type' => 'post' ) );
		$image_id = $this->create_attachment( $post_id );

		$this->assertFalse( user_can( $this->operator_id, 'edit_post', $image_id ) );
		$this->assertFalse( user_can( $this->operator_id, 'edit_post_meta', $image_id, '_wp_attachment_image_alt' ) );
	}

	/**
	 * An image uploaded to an asset (as the featured-image modal does) is editable.
	 *
	 * @return void
	 */
	public function test_operator_can_edit_an_image_uploaded_to_an_asset() {
		$image_id = $this->create_attachment( $this->create_asset() );

		$this->assertTrue( user_can( $this->operator_id, 'edit_post', $image_id ) );
		$this->assertTrue( user_can( $this->operator_id, 'edit_post_meta', $image_id, '_wp_attachment_image_alt' ) );
	}

	/**
	 * An image from the library that is the featured image of an asset is editable, until it is replaced.
	 *
	 * @return void
	 */
	public function test_operator_can_edit_the_featured_image_of_an_asset_until_it_is_replaced() {
		$asset_id = $this->create_asset();
		$old_id   = $this->create_attachment();
		$new_id   = $this->create_attachment();

		$this->assertFalse( user_can( $this->operator_id, 'edit_post', $old_id ), 'Not featured yet.' );

		set_post_thumbnail( $asset_id, $old_id );
		$this->assertTrue( user_can( $this->operator_id, 'edit_post', $old_id ) );
		$this->assertFalse( user_can( $this->operator_id, 'edit_post', $new_id ), 'A different image stays out of reach.' );

		set_post_thumbnail( $asset_id, $new_id );
		$this->assertFalse( user_can( $this->operator_id, 'edit_post', $old_id ), 'Replacing the featured image revokes access to the old one.' );
		$this->assertTrue( user_can( $this->operator_id, 'edit_post', $new_id ) );
	}

	/**
	 * The featured image of a brand-new (auto-draft) asset is editable too.
	 *
	 * @return void
	 */
	public function test_operator_can_edit_the_featured_image_of_an_auto_draft_asset() {
		$asset_id = $this->create_asset( 'auto-draft' );
		$image_id = $this->create_attachment();
		set_post_thumbnail( $asset_id, $image_id );

		$this->assertTrue( user_can( $this->operator_id, 'edit_post', $image_id ) );
	}

	/**
	 * A featured image of ordinary content does not open the image to operators.
	 *
	 * @return void
	 */
	public function test_operator_cannot_edit_the_featured_image_of_a_regular_post() {
		$post_id  = self::factory()->post->create( array( 'post_type' => 'post' ) );
		$image_id = $this->create_attachment();
		set_post_thumbnail( $post_id, $image_id );

		$this->assertFalse( user_can( $this->operator_id, 'edit_post', $image_id ) );
	}

	/**
	 * Only images are covered: an attachment that is not an image stays out of reach.
	 *
	 * @return void
	 */
	public function test_operator_cannot_edit_a_non_image_attachment_of_an_asset() {
		$pdf_id = $this->create_attachment( $this->create_asset(), 'application/pdf' );

		$this->assertFalse( user_can( $this->operator_id, 'edit_post', $pdf_id ) );
	}

	/**
	 * Only the alternative text meta is opened, not arbitrary attachment meta.
	 *
	 * @return void
	 */
	public function test_operator_edit_post_meta_is_limited_to_the_alt_text() {
		$image_id = $this->create_attachment( $this->create_asset() );

		$this->assertTrue( user_can( $this->operator_id, 'edit_post_meta', $image_id, '_wp_attachment_image_alt' ) );
		$this->assertFalse( user_can( $this->operator_id, 'edit_post_meta', $image_id, '_wp_attached_file' ) );
	}

	/**
	 * Users without the operator capability never gain access, even on asset images.
	 *
	 * @return void
	 */
	public function test_non_operators_never_gain_access() {
		$image_id  = $this->create_attachment( $this->create_asset() );
		$member_id = self::factory()->user->create( array( 'role' => ALMGR_MEMBER_ROLE ) );
		$subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->assertFalse( user_can( $member_id, 'edit_post', $image_id ) );
		$this->assertFalse( user_can( $subscriber_id, 'edit_post', $image_id ) );
	}
}
