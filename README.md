# Content Review module

## Maintainer Contact
* Luke Hudson (Forum Nickname: silverluke)
  <luke (at) silverstripe (dot) com>

## Requirements
 * SilverStripe 2.4 or newer


## Installation

Drop it into your installation folder, and refresh your database schema
through `http://<your-host>/dev/build`.

If you wish to have emails sent when a page comes up for review, you
new to have the DailyTask cron job set up. See ScheduledTask.php

## Usage

When you open a page in the CMS, there will now be a Review tab.

### Next Review Date

If a 'Next review date' is set, then the Review date will be updated automatically whenever the page is saved.

## Customisation

By default, when a page is saved the review date is updated.
Instead, you may do this manually without saving the page content.
To enable this feature, place the following into your mysite/_config.php


    // Make updates manual, rather than on publish
    // This will enable the "Mark as reviewed" button on the Review tab.
    SiteTreeContentReview::set_update_on_write(false);

