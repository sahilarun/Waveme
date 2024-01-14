<?php
/**
 * The template for displaying station archive
 */

get_header();

?>

  <div id="primary" class="content-area">
    <main id="main" class="site-main">
      <?php do_action( 'play_before_archive_header'); ?>
      <header class="archive-header archive-header-with-filter">
          <?php
          $obj = get_queried_object();
          $filter = '';
          if( isset($obj->taxonomy) ){
            $taxonomy = $obj->taxonomy;
            $arg = array(
                'taxonomy'     => $taxonomy,
                'show_count'   => false,
                'echo'         => 0,
                'hierarchical' => true,
                'title_li'     => ''
            );

            $filter_taxonomy = wp_list_categories( apply_filters($taxonomy.'_archive_term_filter', $arg ));
            $filter = sprintf('<span class="dropdown-toggle" data-toggle="dropdown"></span> <div class="dropdown-menu dropdown-term-filter"><ul>%s</ul></div>', $filter_taxonomy);
          }
          the_archive_title( '<h1 class="archive-title">','</h1> '.$filter );
          ?>
      </header>
      <?php do_action( 'play_after_archive_header'); ?>
      <div class="archive-content">
      <?php do_action( 'play_before_archive_content'); ?>
      <?php if ( have_posts() ) :?>
          <?php
          $query_arg = array(
            'type' => get_post_type(),
            'pages' => apply_filters('play_archive_pages', 18),
            'query' => array('tax_query' => array(
                'relation' => 'AND',
              )
            ),
            'pager' => 'more',
            'debug'=> false
          );

          $query = array('tax_query');

          if(is_tax()){
            $query_arg['query']['tax_query'][] =  array(
              'taxonomy' => $obj->taxonomy,
              'field' => 'slug',
              'terms' => $obj->slug
            );
          }

          if(is_post_type_archive('product')){
            $query_arg['query']['tax_query'][] = array(
              'taxonomy'  => 'product_visibility',
              'terms'     => array( 'exclude-from-catalog' ),
              'field'     => 'name',
              'operator'  => 'NOT IN',
            );
          }
          
          $arg = apply_filters('station_archive_'.get_post_type().'_filter', $query_arg);
          
          do_action( 'the_loop_block', $arg);
          ?>
      <?php
      else :
        get_template_part( 'templates/content/content', 'none' );
      endif;
      ?>
      <?php do_action( 'play_after_archive_content'); ?>
      </div>
      <footer class="archive-footer">
        <?php do_action( 'play_before_archive_footer'); ?>
        <div class="archive-description">
          <?php the_archive_description(); ?>
        </div>
        <?php do_action( 'play_after_archive_footer'); ?>
      </footer>
    </main><!-- #main -->
  </div><!-- #primary -->

<?php
get_footer();
