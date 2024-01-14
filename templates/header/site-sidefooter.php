<?php
/**
 * Displays the sidenav page 
 */

defined( 'ABSPATH' ) || exit;

$id = get_option( 'page_sidefooter' );
if(!$id) return;

?>

<footer id="side-footer" class="site-sidebar-footer"><?php
ffl_the_content($id);
?></footer>
