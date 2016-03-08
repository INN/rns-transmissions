<?php
global $post;
printf( '<p>%s</p>', rns_transmissions_thirty() );
printf( '<p>%s</p>', rns_get_daily_report_copyright( $post ) );
printf( '<p><small>%s</small></p>', rns_transmissions_email_footer() );
