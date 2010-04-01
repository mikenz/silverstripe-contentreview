<?php


/**
 * Test the class @see LeftAndMainContentReview
 * @covers LeftAndMainContentReview
 */
class LeftAndMainContentReviewTest extends SapphireTest {
	static $fixture_file = 'contentreview/tests/ContentReviewTest.yml';

	/**
	 * Setup any fixture items, etc.
	 */
	public function setUp() {
		parent::setUp();
		SiteTreeContentReview::set_update_on_write(false);
		Object::add_extension('LeftAndMain', 'LeftAndMainContentReview');
	}


	/**
	 * Test the function @see LeftAndMainContentReview::updateEditForm
	 * @covers LeftAndMainContentReview::updateEditForm
	 * @covers SiteTreeContentReview::set_update_on_write
	 */
	function testUpdateOnWrite() {
		SiteTreeContentReview::set_update_on_write(false);

		$this->logInWithPermission();
		$lam = new CMSMain();
		$page = $this->objFromFixture('Page', 'home');
		$lam->setCurrentPageID($page->ID);

		$form = $lam->EditForm();
		$this->assertNotNull( $form->Fields()->fieldByName('Root.Review.cms_reviewcontent') );

		SiteTreeContentReview::set_update_on_write(true);
		$form = $lam->EditForm();
		$this->assertNull( $form->Fields()->fieldByName('Root.Review.cms_reviewcontent') );
		
		SiteTreeContentReview::set_update_on_write(false);
	}



	/**
	 * Test the function @see LeftAndMainContentReview::updateEditForm
	 * @covers LeftAndMainContentReview::updateEditForm
	 */
	function testAuthorPermissions() {
		$author = $this->objFromFixture('Member', 'author');
		$this->assertTrue( Permission::get_members_by_permission('EDIT_CONTENT_REVIEW_FIELDS')->containsIDs(array($author->ID)), "Author doesn't have permission to edit contentreview fields");

		$lam = new CMSMain();
		$page = $this->objFromFixture('Page', 'home');
		$lam->setCurrentPageID($page->ID);

		$author->logIn();
		$this->assertEquals(Controller::curr()->CurrentMember()->ID, $author->ID, "Author is not logged in (according to Controller)!");
		$this->assertEquals(Member::currentUserID(), $author->ID, "Author is not logged in (according to Member::currentUserID)!");

		$form = $lam->EditForm();
		$this->assertNotNull( $form->Fields()->fieldByName('Root.Review.cms_reviewcontent'), "Content review button does not appear for author");
	}

	/**
	 * Test the function @see LeftAndMainContentReview::updateEditForm
	 * @covers LeftAndMainContentReview::updateEditForm
	 */
	function testEditorPermissions() {
		$editor = $this->objFromFixture('Member', 'editor');

		$lam = new CMSMain();
		$page = $this->objFromFixture('Page', 'home');
		$lam->setCurrentPageID($page->ID);

		$editor->logIn();
		$form = $lam->EditForm();
		$this->assertNotNull( $form->Fields()->fieldByName('Root.Review.cms_reviewcontent') );
	}

	/**
	 * Test the function @see LeftAndMainContentReview::cms_reviewcontent
	 * @covers LeftAndMainContentReview::cms_reviewcontent
	 */
	function testCmsReviewContent() {
		$lam = new CMSMain();
		$page = $this->objFromFixture('Page', 'home');
		$lam->setCurrentPageID($page->ID);
		$form = $lam->EditForm();

		$notes = 'Test review';
		$data = array(
				'ID'			=> $page->ID,
				'ReviewNotes'	=> $notes,
				'ReviewPeriodDays' => '7',
				'NextReviewDate'=> $page->NextReviewDate
			);
		
		$lam->cms_reviewcontent($data, $form);

		$date = new Date();
		$date->setValue(strtotime('+ 7 days'));
		// Ensure page is up-to-date from DB.
		$page = DataObject::get_by_id('Page', $page->ID);

		$this->assertEquals($page->NextReviewDate, $date->URLDate());
		$this->assertEquals($page->ReviewNotes, $notes);
	}
}

?>