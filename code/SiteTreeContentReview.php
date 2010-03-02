<?php
/**
 * Set dates at which content needs to be reviewed and provide
 * a report and emails to alert to content needing review
 *
 * @package contentreview
 */
class SiteTreeContentReview extends DataObjectDecorator implements PermissionProvider {
	
	function extraStatics() {
		return array(
			'db' => array(
				"ReviewPeriodDays" => "Int",
				"NextReviewDate" => "Date",
				'ReviewNotes' => 'Text'
			),
			'many_many' => array(
				'Owners' => 'Member'
			),
		);
	}
	
	public function updateCMSFields(&$fields) {
		if(Permission::check("EDIT_CONTENT_REVIEW_FIELDS")) {
			
			$cmsUsers = Permission::get_members_by_permission(array("CMS_ACCESS_CMSMain", "ADMIN"));

			$owners = new CheckboxSetField('Owners', 'Content review owners', $cmsUsers->toDropdownMap());

			$fields->addFieldsToTab("Root.Review", array(
				new HeaderField(_t('SiteTreeCMSWorkflow.REVIEWHEADER', "Content review"), 2),
				$owners,
				new CalendarDateField("NextReviewDate", _t("SiteTreeCMSWorkflow.NEXTREVIEWDATE",
					"Next review date (leave blank for no review)")),
				new DropdownField("ReviewPeriodDays", _t("SiteTreeCMSWorkflow.REVIEWFREQUENCY", 
					"Review frequency (the review date will be set to this far in the future whenever the page is published.)"), array(
					0 => "No automatic review date",
					1 => "1 day",
					7 => "1 week",
					30 => "1 month",
					60 => "2 months",
					91 => "3 months",
					121 => "4 months",
					152 => "5 months",
					183 => "6 months",
					365 => "12 months",
				)),
				new TextareaField('ReviewNotes', 'Review Notes')
			));
			// Some custom CSS to make the owners list look better.
			Requirements::customCSS('#Form_EditForm_Owners { max-height: 12em; padding-left: 0.5em; overflow: auto; background: #FFF; }');
			Requirements::customCSS('#Form_EditForm_Owners li.odd { background: #F8F8F8; }');
			Requirements::customCSS('#Form_EditForm_Owners li { margin: 0 !important; padding: 4px 0; }');
		}
	}
	
	function onBeforeWrite() {
		if($this->owner->ReviewPeriodDays && !$this->owner->NextReviewDate) {
			$this->owner->NextReviewDate = date('Y-m-d', strtotime('+' . $this->owner->ReviewPeriodDays . ' days'));
		}
	}
	
	function providePermissions() {
		return array(
			"EDIT_CONTENT_REVIEW_FIELDS" => array(
				'name' => "Set content owners and review dates",
				'category' => _t('Permissions.CONTENT_CATEGORY', 'Content permissions'),
				'sort' => 50
			)
		);
	}
}
