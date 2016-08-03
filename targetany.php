<?php

/*
  Plugin Name: 鸟巢采集器
  Plugin URI: https://github.com/speed/newcrawler-wordpress
  Description: 鸟巢采集器WordPress发布插件，实现采集数据自动发布到WordPress。
  Version: 3.1.3
  Author: NewCrawler
  Author URI: http://www.newcrawler.com
  License: GPL
 */
define('TA_PATH', WP_PLUGIN_DIR . '/newcrawler-wordpress-master');

include TA_PATH . '/ta-functions.php';

global $table_prefix, $ta_tbl_setting;

$ta_tbl_setting = $table_prefix . 'ta_publish_setting';

global $ta_db_version;
$ta_db_version = '3.1.3';

if (is_admin()) {
    /*  利用 admin_menu 钩子，添加菜单 */
    add_action('admin_menu', 'ta_menu');
}

function ta_menu() {
    if (function_exists('add_menu_page')) {
        add_menu_page('鸟巢采集器', '鸟巢采集器', 'administrator', 'newcrawler-wordpress-master/ta-article-setting.php', '', 'dashicons-share-alt');
    }
}

add_action('init', 'ta_auto_post');

function ta_install() {
    $old_db_version = get_option('ta_db_version');
    update_option("ta_db_version", $ta_db_version);
}

register_activation_hook(__FILE__, 'ta_install');

function ta_auto_post() {
    $ta_password = get_option('ta_password', "NewCrawler");
    if ($_GET["__ta"] == "post") {

        if (empty($_POST['__sign']) || $_POST['__sign'] != $ta_password) {
            ta_fail(TA_ERROR_INVALID_PWD, "password is wrong", "发布密码填写错误");
        }
        $title = $_POST["article_title"];
        $content = $_POST["article_content"];

        if (empty($content) && empty($title)) {
            ta_fail(TA_ERROR_MISSING_FIELD, "article_content and article_title are both empty", "文章内容和标题不能都为空！");
        }

        $postStatus = 'publish';
        if (isset($_POST["postStatus"]) && in_array($_POST["postStatus"], array('publish', 'draft'))) {
            $postStatus = $_POST["postStatus"];
        }

        $postPassword = '';
        if (isset($_POST["accessword"]) && $_POST["accessword"]) {
            $postPassword = $_POST["accessword"];
        }

        $my_post = array(
            'post_password' => $postPassword,
            'post_status' => $postStatus,
            'post_author' => 1
        );
        if (!empty($title)) {
            $my_post['post_title'] = htmlspecialchars_decode($title);
        }
        if (!empty($content)) {
            $my_post['post_content'] = htmlspecialchars_decode($content);
        }


        $publish_time = intval($_POST["article_publish_time"]);
        if (!empty($publish_time)) {
            $my_post['post_date'] = date("Y-m-d", $publish_time);
        } else {
            $my_post['post_date'] = date("Y-m-d", time());
        }

        $author = htmlspecialchars_decode($_POST["article_author"]);

        if (!empty($author)) {
            $md5author = substr(md5($author), 8, 16);
            $user_id = username_exists($md5author);
            if (!$user_id) {
                $random_password = wp_generate_password();
                $userdata = array(
                    'user_login' => $md5author,
                    'user_pass' => $random_password,
                    'display_name' => $author,
                );

                $user_id = wp_insert_user($userdata);
                if (is_wp_error($user_id)) {
                    $user_id = 0;
                }
            }
            if ($user_id) {
                $my_post['post_author'] = $user_id;
            }
        }
        $article_categories = $_POST["article_categories"];
        if (!empty($article_categories)) {
            $rawCates = stripslashes($article_categories);
            $cates = json_decode($rawCates);
            if (is_array($cates)) {
                $post_cates = array();
                foreach ($cates as $cate) {
                    $term = term_exists($cate, "category");

                    if ($term === 0 || $term === null) {
                        $term = wp_insert_term($cate, "category");
                    }
                    if ($term !== 0 && $term !== null) {
                        array_push($post_cates, intval($term["term_id"]));
                    }
                }
                if (count($post_cates) > 0) {
                    $my_post['post_category'] = $post_cates;
                }
            }
        }

        $article_topics = $_POST["article_topics"];
        if (!empty($article_topics)) {
            $rawTags = stripslashes($article_topics);
            $tags = json_decode($rawTags);

            if (is_array($tags)) {
                $post_tags = array();
                $term = null;
                foreach ($tags as $tag) {
                    $term = term_exists($tag, "post_tag");

                    if ($term === 0 || $term === null) {
                        $term = wp_insert_term($tag, "post_tag");
                    }
                    if ($term !== 0 && $term !== null) {
                        array_push($post_tags, intval($term["term_id"]));
                    }
                }
                if (count($post_tags) > 0) {
                    $my_post['tags_input'] = $post_tags;
                }
            }
        }

        // Insert the post into the database
        kses_remove_filters();
        $post_id = wp_insert_post($my_post); //wp_insert_user wp_insert_comment wp_insert_category
        kses_init_filters();

        if (empty($post_id)) {
            ta_fail(TA_ERROR_ERROR, "Empty Post ID", "插入系统失败");
        }

				if(!empty($_POST["article_thumbnail"])){
	        $image_url = ta_redirect_url($_POST["article_thumbnail"]); // Define the image URL here
	        //创建thumbnail
	        // Add Featured Image to Post
	        if ($image_url !== false && !empty($post_id)) {
	            $upload_dir = wp_upload_dir(); // Set upload folder
	            $image_data = file_get_contents($image_url['realurl']); // Get image data
	            $suffix = "jpg";
	            $filename = md5($image_url['realurl']) . ".". $suffix; // Create image file name
	            // Check folder permission and define file location
	            if (wp_mkdir_p($upload_dir['path'])) {
	                $file = $upload_dir['path'] . '/' . $filename;
	            } else {
	                $file = $upload_dir['basedir'] . '/' . $filename;
	            }

	            // Create the image  file on the server
	            file_put_contents($file, $image_data);
	            if (file_exists($file)) {
	                //文件存在 在做插入
	                // Check image file type
	                $wp_filetype = wp_check_filetype($filename, null);

	                // Set attachment data
	                $attachment = array(
	                    'post_mime_type' => $wp_filetype['type'],
	                    'post_title' => sanitize_file_name($filename),
	                    'post_content' => '',
	                    'post_status' => 'inherit'
	                );

	                // Create the attachment
	                $attach_id = wp_insert_attachment($attachment, $file, $post_id);

	                // Include image.php
	                require_once(ABSPATH . 'wp-admin/includes/image.php');

	                // Define attachment metadata
	                $attach_data = wp_generate_attachment_metadata($attach_id, $file);

	                // Assign metadata to attachment
	                wp_update_attachment_metadata($attach_id, $attach_data);

	                // And finally assign featured image to post
	                set_post_thumbnail($post_id, $attach_id);
	            }
	        }
	      }

				//插入评论
				$comment_json = preg_replace("/[\r\n\t]/", '', $_POST['article_comment']);
				//ta_fail(TA_ERROR_ERROR, "DEBUG", "".$comment_json);
				$article_comment = json_decode(stripslashes($comment_json), true);

				if($article_comment != null && is_array($article_comment)){
					foreach($article_comment as $comment){
						$content = $comment["article_comment_content"];
						if(!empty($content)){
							//内容不是空

							$pub_time = $comment["article_comment_publish_time"];
							if(empty($pub_time)){
									$pub_time = time();
							}

							$cdata = array(
							    'comment_post_ID' => $post_id,
							    'comment_content' => $content,
							    'comment_type' => '',
							    'comment_parent' => 0,
							    'comment_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)',
							    'comment_date' => date("Y-m-d H:i:s",$pub_time),
							    'comment_approved' => 1,
							);

							$cauthor = $comment["article_comment_author"];

			        if (!empty($cauthor)) {
			            $cmd5author = substr(md5($cauthor), 8, 16);
			            $cuser_id = username_exists($cmd5author);
			            if (!$cuser_id) {
			                $random_password = wp_generate_password();
			                $cuserdata = array(
			                    'user_login' => $cmd5author,
			                    'user_pass' => $random_password,
			                    'display_name' => $cauthor,
			                );

			                $cuser_id = wp_insert_user($cuserdata);
			                if (is_wp_error($cuser_id)) {
			                    $cuser_id = 0;
			                }
			            }
			            if($cuser_id != 0){
			            	$cdata["user_id"] = $cuser_id;
			            }
			            $cdata["comment_author"] = $cauthor;
			            $cdata['comment_author_IP'] = ta_random_ip();
			        }

							wp_insert_comment($cdata);
						}
					}
				}



        ta_success(array("url" => get_home_url() . "/?p=" . $post_id));
    } else if ($_GET["__ta"] == "details") {
        if (empty($_POST['__sign']) || $_POST['__sign'] != $ta_password) {
            ta_fail(TA_ERROR_INVALID_PWD, "password is wrong", "发布密码填写错误");
        }
        $ret = array(array("value" => "", "text" => urlencode("爬取到的分类")));

        if ($_POST["type"] === "cate") {
            $cates = get_terms('category', 'orderby=count&hide_empty=0');

            foreach ($cates as $cate) {
                array_push($ret, array("value" => urlencode($cate->name), "text" => urlencode($cate->name)));
            }
        }

        ta_success($ret);
    } else if ($_GET["__ta"] == "version") {
        global $wp_version;
        if (empty($_POST['__sign']) || $_POST['__sign'] != $ta_password) {
            ta_fail(TA_ERROR_INVALID_PWD, "password is wrong", "发布密码填写错误");
        }
        $info = array(
            'protocol' => '1',
            'protocolVersion' => '1',
            'supportStdVersion' => array(
                'article' => '1.0.0',
                'question' => '1.0.0'
            ),
            'php' => PHP_VERSION,
            'supportVersion' => 'wp 4.5.1',
            'version' => '3.1.3',
            'pubVersion' => '3.1.3',
            'versionDetail' => array('wp' => $wp_version),
            'otherInfo' => array()
        );
        ta_success($info);
    }
}