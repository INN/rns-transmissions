<?php

/**
 * Debugging for creating the draft campaign.
 */
if($draft_result->was_successful()) {
    // echo "Created with ID\n<br />".$draft_result->response;
} else {
    echo 'Failed with code '.$draft_result->http_status_code."\n<br /><pre>";
    var_dump($draft_result->response);
    echo '</pre>';
}

// Uncomment to debug
if($send_result->was_successful()) {
  // echo "Scheduled with code\n<br />".$send_result->http_status_code;
} else {
  echo 'Failed with code '.$send_result->http_status_code."\n<br /><pre>";
  var_dump($send_result->response);
  echo '</pre>';
}
