<?php

/*
Plugin Name: By this Author
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the plugin.
Version: 1.1.0
Author: Racanu
#Author URI: http://URI_Of_The_Plugin_Author
Text Domain: by-this-author
#Domain Path: Optional. Plugin's relative directory path to .mo files. Example: /locale/
#Network: Optional. Whether the plugin can only be activated network wide. Example: true
#License: A short license name. Example: GPL2
*/

defined('ABSPATH') or die ('No direct access to this file.');

class By_This_Author
{
	public function __construct()
	{
		add_action('init', array($this, 'i18n'));
		//add_filter('the_title', array($this, 'title_filter'));
		add_shortcode('by-this-author', array($this, 'process_by_this_author'), 1);
		add_shortcode('get-age', array($this, 'get_age_from_date'), 1);
		add_shortcode('time-machine', array($this, 'time_machine'), 1);
	}

	function i18n()
	{
 		load_plugin_textdomain('by-this-author', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
	}

	/*
	function title_filter($val)
	{
		return '|' . $val . '|';
	}
	*/


	function get_age($ref_date, $end_date)
	{
		return floor(($end_date - $ref_date)/31556926);
	}

	function get_age_from_date($atts)
	{
		// Extract shortcode atts
		extract( shortcode_atts( array(
			'ref_date'  => '',
			'end_date'  => date('Y-m-d'),
			), $atts ) );
		return $this->get_age(strtotime($ref_date), strtotime($end_date));
	}

	function time_machine($atts)
	{
		// Extract shortcode atts
		extract( shortcode_atts( array(
			'ref_time'  => '',
			'future_text'  => '',
			'past_text' => '',
			), $atts ) );
		$start_of_today = date('Y-m-d H:i', strtotime(date('Y-m-d')));
		return (strtotime($start_of_today) <= strtotime($ref_time)) ? $future_text : $past_text;
	}

	function get_posts_by_user_name($user_name)
	{
		$user_name = esc_sql($user_name);

		global $wpdb;

		$users = $wpdb->get_results('SELECT ID, display_name
			FROM $wpdb->users
			WHERE display_name LIKE '%esc_sql($user_name)%'
			ORDER BY display_name');

		if (empty($users))
		{
			return array();
		}

		$user = $users[0];
		return get_posts(array('author' => $user->ID, 'posts_per_page' => -1,));
	}

	function get_posts_by_category_name($category_name)
	{
		$cat_id = get_cat_ID($category_name);
		if ($cat_id == 0)
			return array();
		return get_posts(array('cat' => $cat_id, 'posts_per_page' => -1,));
	}

	function get_posts_by_tag($tag_name)
	{
		$tag_term = get_term_by('name', $tag_name, 'post_tag');
		if (!$tag_term)
			return array();
		return get_posts(array('tag__in' => array($tag_term->term_id), 'posts_per_page' => -1,));
	}

	function generate_list($name, $posts, $posts_per_page = null)
	{
		if (empty($posts))
			return '';

		$retval = $retval . '<ul>';
		foreach (array_slice($posts, 0, $posts_per_page) as $post_id)
			$retval = $retval . '<li><a href="' . get_permalink($post_id) . '">' . get_post($post_id)->post_title . '</a></li>';
		$retval = $retval . '</ul>';

		return $retval;
	}

	function process_by_this_author($atts)
	{
		// Extract shortcode atts
		extract( shortcode_atts( array(
			'name'	         => '',
			'post_types'     => 'authored_by, attributed_to',
			'posts_per_page' => null,
			), $atts ) );

		#$post_types_list = split(' *, *', $post_types);
		$post_types_list = array_map('trim', explode(',', $post_types));

		$posts = array();
		$list = '';
		$post_types_text = '';

		if (in_array('authored_by', $post_types_list) and in_array('attributed_to', $post_types_list))
		{
			$posts = array_unique(array_merge($this->get_posts_by_user_name($name), $this->get_posts_by_category_name($name), $this->get_posts_by_tag($name)), SORT_REGULAR);
			$list = $this->generate_list($name, $posts, $posts_per_page);
			$post_types_text = __('authored by or attributed to', 'by-this-author');
		}
		else if (in_array('authored_by', $post_types_list))
		{
			$posts = $this->get_posts_by_user_name($name);
			$list = $this->generate_list($name, $posts, $posts_per_page);
			$post_types_text = __('authored by', 'by-this-author');
		}
		else if (in_array('attributed_to', $post_types_list))
		{
			$posts = array_unique(array_merge($this->get_posts_by_category_name($name), $this->get_posts_by_tag($name)), SORT_REGULAR);
			$list = $this->generate_list($name, $posts, $posts_per_page);
			$post_types_text = __('attributed to', 'by-this-author');
		}
		else
		{
			return '<p>' . sprintf(__('Unknown post types: %s', 'by-this-author'), $post_types_text) . '</p>';
		}

		if (empty($posts))
			return '<p>' . sprintf(__('No posts %s %s found.', 'by-this-author'), $post_types_text, $name) . '</p>';

		return '<p>' . sprintf(__('Posts %s %s:', 'by-this-author'), $post_types_text, $name) . '</p>' . $list;
	}
}

new By_This_Author();
