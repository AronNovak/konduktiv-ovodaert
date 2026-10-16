<?php

/**
 * @file
 * Settings for the home.pl production host (petoovodaert.hu).
 *
 * Included from the server-managed settings.php, which holds the credentials
 * and is not in the repository.
 */

declare(strict_types=1);

// home.pl has no Solr, so both servers only produce connection errors on
// every cron run and status report. Site search is unavailable there.
$config['search_api.server.solr']['status'] = FALSE;
$config['search_api.server.pantheon_search']['status'] = FALSE;
