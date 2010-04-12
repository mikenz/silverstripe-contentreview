<?php

/**
 * Decorate LeftAndMain to provide a "Mark as reviewed" button to instantly update
 * a page's review fields, without saving or publishing.
 */
class LeftAndMainContentReview extends LeftAndMainDecorator {
	
	public static $allowed_actions = array(
		'cms_reviewcontent'
	);

	
	public function init() {
		parent::init();
		/* If review dates are updated when the pages are saved, then don't bother showing the manual Review button */
		if (SiteTreeContentReview::get_update_on_write()) {
			return;
		}

		Requirements::javascript('contentreview/javascript/LeftAndMainContentReview.js');
	}


	/**
	 * Add custom button to edit form, if permissions and conditions are correct.
	 *
	 * @param Form $form
	 * @return None
	 */
	public function updateEditForm(&$form) {
		/* If review dates are updated when the pages are saved, then don't bother showing the manual Review button */
		if (SiteTreeContentReview::get_update_on_write()) {
//			print "updateEditForm(): update_on_write is on -- skipping...\n";
			return;
		}
		$canEdit = Permission::check("EDIT_CONTENT_REVIEW_FIELDS");
		if (!$this->owner->currentPageID()) {
			//print "updateEditForm(): No current page -- skipping...\n";
			return;
		}
		if (!$canEdit) {
			//print "updateEditForm(): No contentreview perms -- skipping...\n";
			return;
		}

		$record = $this->owner->currentPage();
		if(!$record->canEdit() || $record->IsDeletedFromStage) {
			//print "updateEditForm(): Can't edit, or is deleted -- skipping...\n";
			return;
		}

		$form->Fields()->addFieldToTab('Root.Review', new LiteralField('cms_reviewcontent','<input id="cms_reviewcontent" type="submit" value="Mark as Reviewed" /> (this will save the dates and notes from this tab)'));
	}


	/**
	 * AJAX-friendly action to update the review date/time when the
	 * "Mark as Reviewed" button is clicked
	 *
	 * @param array $data
	 * @param Form $form
	 * @return string (from FormResponse)
	 */
	public function cms_reviewcontent($data, $form) {
		/* If review dates are updated when the pages are saved, then don't bother showing the manual Review button */
		if (SiteTreeContentReview::get_update_on_write()) {
			return;
		}
		$page = DataObject::get_by_id('Page', $data['ID']);
		if ($page) {
			$page->ReviewNotes = $data['ReviewNotes'];
			$page->NextReviewDate = $data['NextReviewDate'];
			$page->ReviewPeriodDays = $data['ReviewPeriodDays'];
			$page->updateReviewDate();
			$page->write();
			FormResponse::status_message("Marked page as reviewed", 'good');
			$fld = $form->dataFieldByName('NextReviewDate');
			$fld->setValue($page->NextReviewDate);
			FormResponse::update_dom_id('NextReviewDate', $fld->FieldHolder(), 'replace');
		} else {
			FormResponse::status_message("Failed to find page", 'bad');
		}
		return FormResponse::respond();
	}

}

?>
