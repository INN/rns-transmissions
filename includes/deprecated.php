<?php
// add_filter( 'wp_insert_post_data', 'rns_filter_transmission', '99', 2 );
/**
 * Filter Transmission content before it hits the database
 *
 * http://www.mail-archive.com/php-general@lists.php.net/msg09723.html
 * http://stackoverflow.com/questions/4366730/php-how-to-check-if-a-string-contain-specific-words
 * http://stackoverflow.com/questions/758488/php-delete-first-four-lines-from-the-top-in-content-stored-in-a-variable
 * http://codex.wordpress.org/Plugin_API/Filter_Reference/wp_insert_post_data
 */
function rns_filter_transmission( $data, $postarr ) {

  if ( $postarr['post_type'] != 'rns_transmission' || $data['post_content'] == "" ) return $data; // Thanks Edit Flow for `return $data`

  // get the post content from the data array
  $content = $data['post_content'];

  // do something with the post data
  // example: $content = str_replace( 'ADVISORY', 'BACON', $content );

  // break the content into an array
  $content_array = explode( "\n", $content );

  // define words that will trigger the deletion of a line
  $disallowed = array( 'ident:', 'selector:', 'priority:', 'category:', 'format:', 'slug:', '^Categories', ' END ', 'nbsp' );

  // set a flag so that we can conduct replacements on only stories
  $is_budget = false;
  // these words indicate we're looking at a budget
  $budget_tipoffs = array( 'OPENING BUDGET', 'UPDATED BUDGET' );

  $slug = '';

  foreach ( $content_array as $key => $line ) {
    // before we excise disallowed lines, extract the story slug for the preamble
    if ( strpos( $line, 'slug:' ) !== false ) {
      $slug = str_replace( array( 'slug: ^', '<', 'BC-' ), '', $line);
    }
    // for each value in the array, search the value for every disallowed word.
    // if a disallowed word is found in value, then remove it from the array
    foreach ( $disallowed as $word ) {
      if ( strpos( $line, $word ) !== false ) {
        unset( $content_array[$key] );
      }
    }
    // switch on the is_budget flag if our budget tipoff lines are the text
    foreach ( $budget_tipoffs as $tipoff ) {
      if ( stripos( $line, $tipoff ) !== false )
        $is_budget = true;
    }
    // if the 'c. 201n' copyright line is there, add a
    // line break after it to create a new paragraph
    if ( preg_match( '/^\^c\. 201/', $line ) ) {
      $content_array[$key] = $line . "\n";
    }
  }

  // do other cleanup:
  // erase lines of dots
  $content_array = str_replace( '............................................................', '', $content_array );
  $content_array = str_replace( '....................', '', $content_array );
  $content_array = str_replace( '...................', '', $content_array );
  $content_array = str_replace( '.............', '', $content_array );
  $content_array = str_replace( '............', '', $content_array );
  $content_array = str_replace( '...........', '', $content_array );
  $content_array = str_replace( '..........', '', $content_array );
  $content_array = str_replace( '.........', '', $content_array );
  $content_array = str_replace( '........', '', $content_array );
  $content_array = str_replace( '.......', '', $content_array );
  // fix ^M characters
  // $line = preg_replace("\r\n|\n|\r", "\n", $line);
  // fix ` and '
  $content_array = str_replace( '``', '"', $content_array );
  $content_array = str_replace( "\'\'\'", '\'"', $content_array );
  $content_array = str_replace( "\'\'", '"', $content_array );
  $content_array = str_replace( "`", "'", $content_array );
  $content_array = str_replace( "\'", "'", $content_array );
  // fix _
  $content_array = str_replace( " _ ", " -- ", $content_array );
  // one-off weirdness
  $content_array = str_replace( "Õ", "'", $content_array );
  $content_array = str_replace( "Ô", "'", $content_array );
  $content_array = str_replace( "Ò", '"', $content_array );
  $content_array = str_replace( "Ó", '"', $content_array );

  // remove carets from the beginning and angle brackets
  // from the end of lines in story preambles
  $content_array = preg_replace( '/^\^/', '', $content_array );
  $content_array = preg_replace( '/<\r$/', '', $content_array );

  // Do story substitutions if this is not a budget and there is post content
  if ( ! $is_budget && $does_not_exist ) {
    // assume the four spaces at the start of lines mark new paragraphs in stories
    $content_array = preg_replace( '/^    /', "\n", $content_array );

    // add the preamble to the top of a story
    $sep = "========================================";
    $boilerplate = "RNS E-WIRE is transmitted as the stories are edited. Please refer to the RNS Opening Budget for information on today's planned stories.";
    array_unshift( $content_array,
      $sep,
      date( 'l, F d, Y', current_time( 'timestamp' ) ),
      $sep,
      $boilerplate,
      $sep,
      $slug,
      // skip the last separator if a slug wasn't generated
      $slug ? $sep . "\n" : "\n"
    );
  }

  // protect against any extra line breaks inserted by the copyright insertion above
  $content_array = str_replace( "\n\r", '', $content_array );

  // put the content back together and update the data array
  $content = implode( "\n", $content_array );
  $data['post_content'] = $content;

  return $data;

}

/**
 * Return the name of the editor that goes on top of daily reports
 *
 * @return string
 */
function rns_get_daily_report_editor() {
  return 'Kevin Eckstrom, Editor-in-Chief';
}

