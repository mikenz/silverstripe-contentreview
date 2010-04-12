<?php

/**
 * Show all pages that need to be reviewed
 *
 * @package contentreview
 */
class PagesDueForReviewReport extends SS_Report {
	function title() {
		return _t('PagesDueForReviewReport.TITLE', 'Pages due for review');
	}
	
	function parameterFields() {
		$params = new FieldSet();
		
		// We need to be a bit fancier when subsites is enabled
		if(class_exists('Subsite') && $subsites = DataObject::get('Subsite')) {
			// javascript for subsite specific owner dropdown
			Requirements::javascript('contentreview/javascript/PagesDueForReviewReport.js');

			// Remember current subsite
			$existingSubsite = Subsite::currentSubsiteID();
			
			$map = array();

			// Create a map of all potential owners from all applicable sites
			$sites = Subsite::accessible_sites('CMS_ACCESS_CMSMain');
			foreach($sites as $site) {
				Subsite::changeSubsite($site);

				$cmsUsers = Permission::get_members_by_permission(array("CMS_ACCESS_CMSMain", "ADMIN"));
				// Key-preserving merge
				foreach($cmsUsers->toDropdownMap('ID', 'Title') as $k => $v) {
					$map[$k] = $v;
				}
			}
			
			$map = $map + array('' => 'Any', '-1' => '(no owner)');
				
			$params->push(new DropdownField("OwnerID", 'Page owner', $map));
			
			// Restore current subsite
			Subsite::changeSubsite($existingSubsite);
		} else {
			$cmsUsers = Permission::get_members_by_permission(array("CMS_ACCESS_CMSMain", "ADMIN"));
			$map = $cmsUsers->map('ID', 'getName', '(no owner)');
			unset($map['']);
			asort($map);
			$map = array('' => 'Any', '-1' => '(no owner)') + $map;
			$params->push(new DropdownField("OwnerID", 'Page owner', $map));
		}
		
		$params->push($startDate = new CalendarDateField('ReviewDateAfter', 'Review date after or on (DD/MM/YYYY)'));
		$params->push($endDate = new CalendarDateField('ReviewDateBefore', 'Review date before or on (DD/MM/YYYY)', date('d/m/Y', strtotime('midnight'))));
		$endDate->mustBeAfter($startDate->Name());
		$startDate->mustBeBefore($endDate->Name());

		$params->push(new CheckboxField('ShowVirtualPages', 'Show Virtual Pages'));
		
		return $params;
	}
	
	function columns() {
		$fields = array(
			'Title' => array(
				'title' => 'Page name',
				'formatting' => '<a href=\"admin/show/$ID\" title=\"Edit page\">$value</a>'
			),
			'NextReviewDate' => array(
				'title' => 'Review Date',
				'casting' => 'Date->Full'
			),
			'OwnerNames' => array(
				'title' => 'Owner'
			),
			'LastEditedByName' => 'Last edited by',
			'AbsoluteLink' => array(
				'title' => 'URL',
				'formatting' => '$value " . ($AbsoluteLiveLink ? "<a target=\"_blank\" href=\"$AbsoluteLiveLink\">(live)</a>" : "") . " <a target=\"_blank\" href=\"$value?stage=Stage\">(draft)</a>'
			)
		);
		
		return $fields;
	}
		
	function sourceRecords($params, $sort, $limit) {
		$wheres = array();
		
		
		$db = DB::getConn();
		if(empty($params['ReviewDateBefore']) && empty($params['ReviewDateAfter'])) {
			// If there's no review dates set, default to all pages due for review now
			$now = class_exists('SS_Datetime')
				? SS_Datetime::now()->Rfc2822()
				: SSDatetime::now()->Rfc2822();
			$wheres[] = $db->datetimeDifferenceClause('NextReviewDate', $now) . ' < 0';
		} else {
			// Review date before
			if(!empty($params['ReviewDateBefore'])) {
				list($day, $month, $year) = explode('/', $params['ReviewDateBefore']);
				$reviewDate = "$year-$month-$day 00:00:00";
				$wheres[] = $db->datetimeDifferenceClause('NextReviewDate', $db->datetimeIntervalClause(Convert::raw2sql($reviewDate), '1 DAY')) . ' < 0';
			}
			
			// Review date after
			if(!empty($params['ReviewDateAfter'])) {
				list($day, $month, $year) = explode('/', $params['ReviewDateAfter']);
				$reviewDate = "$year-$month-$day 00:00:00";
				$wheres[] = $db->datetimeDifferenceClause('NextReviewDate', Convert::raw2sql($reviewDate)) . ' >= 0';
			}
		}
		
		// Show virtual pages?
		if(empty($params['ShowVirtualPages'])) {
			$wheres[] = '"SiteTree"."ClassName" != \'VirtualPage\' AND "SiteTree"."ClassName" != \'SubsitesVirtualPage\'';
		}
		
		// We use different dropdown depending on the subsite
		$ownerIdParam = 'OwnerID';

		
		// Owner dropdown
		if(!empty($params[$ownerIdParam])) {
			$ownerID = (int)$params[$ownerIdParam];
			$ownerWhere = '';
			// We use -1 here to distinguish between No Owner and Any
			if($ownerID == -1) {
				$wheres[] = '"SiteTree_Owners"."MemberID" IS NULL';
			} else {
				$wheres[] = '"SiteTree_Owners"."MemberID" = ' . $ownerID;
			}
		}
		
		$query = singleton("SiteTree")->extendedSQL(join(' AND ', $wheres));
		
//		$query->select[] = Member::get_title_sql('Owner').' AS OwnerNames';
		if(!empty($params[$ownerIdParam])) {
			$query->from[] = 'LEFT JOIN "SiteTree_Owners" ON "SiteTree_Owners"."SiteTreeID" = "SiteTree"."ID"';
			$query->from[] = 'LEFT JOIN "Member" AS "Owner" ON "Owner"."ID" = "SiteTree_Owners"."MemberID"';
		}
		
		// Turn a query into records
		if($sort) {
			$parts = explode(' ', $sort);
			$field = $parts[0];
			$direction = $parts[1];
			
			if($field == 'AbsoluteLink') {
				$sort = 'URLSegment ' . $direction;
			} elseif($field == 'Subsite.Title') {
				$query->from[] = 'LEFT JOIN "Subsite" ON "Subsite"."ID" = "SiteTree"."SubsiteID"';
			}
			
			if($field != "LastEditedByName") {
				$query->orderby = $sort;
			}
		}

		$records = singleton('SiteTree')->buildDataObjectSet($query->execute(), 'DataObjectSet', $query);
//		 var_dump($records);
		if($records) {
			foreach($records as $record) {
				$record->LastEditedByName = $record->LastEditedBy() ? $record->LastEditedBy()->getName() : null;
				$ownerstr = array();
				foreach($record->Owners() as $owner) {
					$ownerstr[] = $owner->getName();
				}
				$ownerstr = implode(', ', $ownerstr);
				$record->OwnerNames = $ownerstr;
			}
		
			if($sort && $field != "LastEditedByName") $records->sort($sort);
		
			// Apply limit after that filtering.
			if($limit) return $records->getRange($limit['start'], $limit['limit']);
			else return $records;
		}
	}
}

?>
