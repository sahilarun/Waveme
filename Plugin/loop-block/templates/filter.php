<?php
/**
 * Template part for displaying loop filter
 * filter::key[__value][name] format
 */

?>
<div class="loop-filter">
	<?php do_action( 'loop_block_filter_before', $filters ); ?>
	<?php
	foreach ( $filters as $kk => $vv ){
		foreach ( $vv as $key => $value ){
			$items = [];
			$i = 0;
			$active = [];
			$default = '';
			$more = '';
			$heading = '';
			foreach ( $value as $k => $v ){
				$selected = false;
				if($i == 0){
					$default = $v['name'];
				}

				if(isset($v['active'])){
					$active[] = $v['name'];
					$selected = true;
				}

				// heading
				if(strtolower($k) === 'heading'){
					$heading = sprintf( '<div class="loop-filter-item-heading">%s</div>', esc_html($v['name']) );
					unset($value[$k]);
					continue;
				}

				$items[] = sprintf('<a href="%s" class="dropdown-item '.esc_attr($selected ? 'selected' : '').'">%s <span class="loop-filter-count">%s</span></a>', esc_url( $v['url'] ), esc_html( $v['name'] ), esc_html( (isset($v['count']) ? $v['count'] : '') ));
				$i++;
			}

			// selected
			if(empty($active)){
				$active[] = $default;
				array_shift($items);
			}elseif(count($active) > 2){
				$active = array_slice($active, -2);
				$more = '...';
			}

			// range::bpm__120-300[BPM]
			if( $kk == 'range' ){
				$items = [];
				foreach ( $value as $k => $v ){
					$m = explode('-', $k);
					$url = $v['url'];
					$mm = explode('-', $v['value']);
				}

				$items[] = sprintf( '<div class="multi-range"><input type="range" multiple value="%d,%d" min="%d" max="%d" data-url="%s" name="%s" data-plugin="range"></div>', esc_attr( $mm[0] ), esc_attr( $mm[1] ), esc_attr( $m[0] ), esc_attr( $m[1] ), esc_attr( $url ), esc_attr($key) );
			}
			echo '<div class="loop-filter-item loop-filter-item-'.esc_attr($kk).'-'.esc_attr($key).'">'.$heading.'<a href="#" class="dropdown-toggle button" data-toggle="dropdown">'.esc_html ( implode(', ',$active).$more ).'</a><div class="dropdown-menu">'.implode('', $items).'</div></div>';
			
		}
	}
	?>
	<?php do_action( 'loop_block_filter_after', $filters ); ?>
</div>
