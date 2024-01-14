<?php
/**
 * Custom template tags for this theme
 */

if ( ! function_exists( 'ffl_posted_on' ) ) :
	/**
	 * Prints HTML with meta information for the current post-date/time.
	 */
	function ffl_posted_on($echo = true) {
		$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
		if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
			$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
		}

		$time_string = sprintf(
			$time_string,
			esc_attr( get_the_date( DATE_W3C ) ),
			esc_html( get_the_date() ),
			esc_attr( get_the_modified_date( DATE_W3C ) ),
			esc_html( get_the_modified_date() )
		);

		$output = sprintf(
			'<span class="posted-on"><a href="%1$s" rel="bookmark">%2$s</a></span>',
			esc_url( get_permalink() ),
			$time_string
		);

		if($echo){
			echo ''.$output;
		}else{
			return $output;
		}
	}
endif;

if ( ! function_exists( 'ffl_posted_by' ) ) :
	/**
	 * Prints HTML with meta information about theme author.
	 */
	function ffl_posted_by($echo = true) {
		$output = sprintf(
			/* translators: 1: SVG icon. 2: post author, only visible to screen readers. 3: author link. */
			'<span class="author-bio"><a class="author-link" href="%1$s">%2$s</a><span class="author-info"><a class="author-title" href="%3$s">%4$s</a>%5$s</span></span>',
			esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
			get_avatar(get_the_author_meta( 'ID' ), 48),
			esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
			esc_html( get_the_author() ),
			ffl_posted_on(false)
		);
		if($echo){
			echo ''.$output;
		}else{
			return $output;
		}
	}
endif;

if ( ! function_exists( 'ffl_posted_byline' ) ) :
	/**
	 * Prints HTML with meta information about theme author.
	 */
	function ffl_posted_byline($echo = true) {
		$output = sprintf(
			'<a class="byline" href="%1$s">%2$s<span class="author-name">  %3$s</span></a>',
			esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
			get_avatar(get_the_author_meta( 'ID' ), 48),
			esc_html( get_the_author() )
		);
		if($echo){
			echo ''.$output;
		}else{
			return $output;
		}
	}
endif;

if ( ! function_exists( 'ffl_comment_count' ) ) :
	/**
	 * Prints HTML with the comment count for the current post.
	 */
	function ffl_comment_count() {
		if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
			echo '<span class="comments-link">';

			comments_popup_link( esc_html__('Comment','waveme'), esc_html__('1 Comment','waveme'), esc_html__('% Comments','waveme') );

			echo '</span>';
		}
	}
endif;


function ffl_comment_date_output($date, $format, $comment){
	if($format !== ''){
		return $date;
	}
    $ago = sprintf( esc_html__('%s ago','waveme'), human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp', 1 ) ) );
    return 'product' == get_post_type($comment->comment_post_ID) ? $ago : '<span class="comment-human-time">'.$ago.'</span>';
}
add_filter('get_comment_date', 'ffl_comment_date_output', 10, 3);


if ( ! function_exists( 'ffl_tags_list' ) ) :
	/**
	 * Prints HTML with meta information for the current post-date/time.
	 */
	function ffl_tags_list() {
		$tags_list = get_the_tag_list( '', ' ' );
		if ( $tags_list ) {
			printf(
				/* translators: 1: SVG icon. 2: list of tags. */
				'<span class="tags">%1$s</span>',
				$tags_list
			); // WPCS: XSS OK.
		}
	}
endif;

if ( ! function_exists( 'ffl_categories_list' ) ) :
	/**
	 * Prints HTML with meta information for the current post-date/time.
	 */
	function ffl_categories_list() {
		$categories_list = get_the_category_list(' ');
		if ( $categories_list ) {
			printf(
				'<span class="cat-links">%1$s</span>',
				$categories_list
			);
		}
	}
endif;

if ( ! function_exists( 'ffl_entry_meta' ) ) :
	/**
	 * Prints HTML with meta information for the categories, tags and comments.
	 */
	function ffl_entry_meta() {
		if ( 'post' === get_post_type() ) {
			// Posted by
			echo '<div class="entry-meta">';
			ffl_posted_byline();
			ffl_posted_on();
			ffl_comment_count();
			edit_post_link('<span class="edit-link">'.esc_html__('Edit','waveme').'</span>');
			echo '</div>';
		}
	}
endif;

if ( ! function_exists( 'ffl_entry_footer' ) ) :
	/**
	 * Prints HTML with meta information for the categories, tags and comments.
	 */
	function ffl_entry_footer() {
		// Edit post link.
		ffl_tags_list();
	}
endif;

if ( ! function_exists( 'ffl_post_thumbnail' ) ) :
	/**
	 * Displays an optional post thumbnail.
	 *
	 * Wraps the post thumbnail in an anchor element on index views, or a div
	 * element when on single views.
	 */
	function ffl_post_thumbnail() {
		if ( ! ffl_can_show_post_thumbnail() ) {
			return;
		}
		$attr = array();
		if ( is_singular() && !is_page() ) :
			?>

			<figure class="post-thumbnail featured-image">
				<?php the_post_thumbnail( 'full', $attr ); ?>
			</figure><!-- .post-thumbnail -->

			<?php
		else :
			?>

		<figure class="post-thumbnail featured-image">
			<a class="post-thumbnail-link" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
				<?php the_post_thumbnail( 'post-thumbnail', $attr ); ?>
			</a>
		</figure>

			<?php
		endif; // End is_singular().
	}
endif;

if ( ! function_exists( 'ffl_the_posts_navigation' ) ) :
	/**
	 * Documentation for function.
	 */
	function ffl_the_posts_navigation() {
		the_posts_pagination(
			array(
				'mid_size'  => 2,
				'prev_text' => sprintf(
					'<span class="nav-prev-text">%s</span>',
					''
				),
				'next_text' => sprintf(
					'<span class="nav-next-text">%s</span>',
					''
				),
			)
		);
	}
endif;

if ( ! function_exists( 'ffl_the_page_navigation' ) ) :
	/**
	 * Documentation for function.
	 */
	function ffl_the_page_navigation() {
		global $post;
		$children = null;
		$exclusions = apply_filters( 'ffl_page_navigation_title_exclusions', array('level','page','test','float') );
		if(ffl_contains($post->post_title, $exclusions)){
			return;
		}
		if(get_post_meta( get_the_ID(), 'hide_pagenav', true )){
			return;
		}
		if ( $post->post_parent && $post->post_parent > 0 ) {
		    $children = wp_list_pages( array(
		        'title_li' => '',
		        'child_of' => $post->post_parent,
		        'echo'     => 0
		    ) );
		} elseif($post->ID > 0) {
		    $children = wp_list_pages( array(
		        'title_li' => '',
		        'child_of' => $post->ID,
		        'echo'     => 0
		    ) );
		};
		
		if ( $children ) : ?>
		<nav class="navigation page-navigation">
			 <?php printf( '<ul class="nav">%s</ul>', $children); ?>
		</nav>
		<?php endif;
	}
endif;

if ( ! function_exists( 'ffl_posted_sticky' ) ) :
	function ffl_posted_sticky() {
		if ( is_sticky() && is_home() && ! is_paged() ) {
			printf( '<span class="sticky-post">%s</span>', esc_html__('Featured','waveme') );
		}
	}
endif;

if ( ! function_exists( 'ffl_link_pages' ) ) :
	function ffl_link_pages() {
		wp_link_pages(
			array(
				'before' => '<div class="page-links">',
				'after'  => '</div>',
			)
		);
	}
endif;
