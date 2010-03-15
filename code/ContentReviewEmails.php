<?php

/**
 * Daily task to send emails to the owners of content items
 * when the review date rolls around
 *
 * @package contentreview
 */
class ContentReviewEmails extends DailyTask {
	function run($req) { $this->process(); }
	function process() {
		// Disable subsite filter (if installed)
		if (ClassInfo::exists('Subsite')) {
			$oldSubsiteState = Subsite::$disable_subsite_filter;
			Subsite::$disable_subsite_filter = true;
		}
		$pages = DataObject::get('Page', "SiteTree.NextReviewDate = '".(class_exists('SS_Datetime') ? SS_Datetime::now()->URLDate() : SSDatetime::now()->URLDate())."'");
		if ($pages && $pages->Count()) {
			foreach($pages as $page) {
				$owners = $page->Owners();
//				var_export($owners);
				if ($owners) foreach($owners as $owner) {
					$sender = Security::findAnAdministrator();
					$recipient = $owner;

					$subject = sprintf(_t('ContentReviewEmails.SUBJECT', 'Page %s due for content review'), $page->Title);

					$email = new Email();
					$email->setTo($recipient->Email);
					$email->setFrom(($sender->Email) ? $sender->Email : Email::getAdminEmail());
					$email->setTemplate('ContentReviewEmails');
					$email->setSubject($subject);
					$subsite = null;
					if (Object::has_extension('SiteTree', 'SiteTreeSubsites')) {
						$subsite = $page->Subsite();
					}
					$email->populateTemplate(array(
						"PageCMSLink" => BASE_URL . "/admin/show/".$page->ID,
						"Recipient" => $recipient,
						"Sender" => $sender,
						"Page" => $page,
						"Subsite" => $subsite,
						"StageSiteLink"	=> $page->AbsoluteLink()."?stage=stage",
						"LiveSiteLink"	=> $page->AbsoluteLink()."?stage=live",
					));

					$email->send();
				}
			}
		}
		
		// Revert subsite filter (if installed)
		if (ClassInfo::exists('Subsite')) {
			Subsite::$disable_subsite_filter = $oldSubsiteState;
		}
	}
}
