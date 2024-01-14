<?php
/**
 * Rank
 */

defined( 'ABSPATH' ) || exit;
$post_id = get_the_ID();

$type = get_post_meta($post_id, 'type', true);
$type_query = '&metaQuery=type__'.$type;

// No type query for sql performance
$type_query = '';

$id = play_get_option( 'page_rank' );

if(empty($id)){
	return;
}

$url = get_permalink( $id );

$rank_week = apply_filters('play_rank', $post_id, 'week', $type);
$rank_month = apply_filters('play_rank', $post_id, 'month', $type);
$rank_year = apply_filters('play_rank', $post_id, 'year', $type);
$rank_all = apply_filters('play_rank', $post_id, 'all', $type);
?>
<ul class="ranks">
	<?php if($rank_week){ ?>
	<li class="rank-week">
		<a href="<?php echo esc_url($url.'?orderby=week'.$type_query);?>">
			<strong>
				<span>#</span><?php esc_html_e($rank_week);?>
			</strong>
			<span><?php play_get_text('this-week', true); ?></span>
		</a>
	</li>
	<?php }?>
	<?php if($rank_month){ ?>
	<li class="rank-month">
		<a href="<?php echo esc_url($url.'?orderby=month'.$type_query);?>">
			<strong>
				<span>#</span><?php esc_html_e($rank_month);?>
			</strong>
			<span><?php play_get_text('this-month', true); ?></span>
		</a>
	</li>
	<?php }?>
	<?php if($rank_year){ ?>
	<li class="rank-year">
		<a href="<?php echo esc_url($url.'?orderby=year'.$type_query);?>">
			<strong>
				<span>#</span><?php esc_html_e($rank_year);?>
			</strong>
			<span><?php play_get_text('this-year', true); ?></span>
		</a>
	</li>
	<?php }?>
	<?php if($rank_all){ ?>
	<li class="rank-all">
		<a href="<?php echo esc_url($url.'?orderby=all'.$type_query);?>">
			<strong>
				<span>#</span><?php esc_html_e($rank_all);?>
			</strong>
			<span><?php play_get_text('all-time', true); ?></span>
		</a>
	</li>
	<?php }?>
</ul>

