# Content Review module

## Maintainer Contact
* Tom Rix (Nickname: trix)
  <tom (at) silverstripe (dot) com>

## Requirements
 * SilverStripe 3.0
 * Database: MySQL, Postgres, SQLite or MSSQL
 * PHP 5.2 or newer (because of Zend_Date usage)

## Installation

Drop it into your installation folder, and refresh your database schema
through `http://<your-host>/dev/build`.

If you wish to have emails sent when a page comes up for review, you
new to have the DailyTask cron job set up. See ScheduledTask.php

## Usage

When you open a page in the CMS, there will now be a Review tab.

## Migration
When migrating from an older version of this module to the current version, 
you might need to run: /dev/tasks/ContentReviewOwnerMigrationTask

