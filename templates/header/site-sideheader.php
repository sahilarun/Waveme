<?php
/**
 * Displays the sidenav page 
 */

defined( 'ABSPATH' ) || exit;

$id = get_option( 'page_sideheader' );
if(!$id) return;

?>

<header id="side-header" class="site-sidebar-header"><?php
ffl_the_content($id);
?></header>
