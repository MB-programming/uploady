<?php
/**
 * Public HTTP entry point for the cron/publish.php worker, for Hostinger plans that only
 * offer a "Visit URL" cron job type instead of direct PHP CLI execution. cron/publish.php
 * itself still enforces the cron_secret check for any non-CLI request — see there.
 */
require __DIR__ . '/cron/publish.php';
