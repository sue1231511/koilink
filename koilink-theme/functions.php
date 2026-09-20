<?php
/**
 * Koilink 主题：动态 CPT + 发布/点赞接口 + 页面自动创建 + PWA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
} );

add_action( 'init', function () {
	// 前台隐藏 WordPress 管理栏（App 化体验，后台 /wp-admin 不受影响）。
	add_filter( 'show_admin_bar', '__return_false' );

	// PWA：让 Service Worker 挂在站点根路径（scope=/）。
	add_rewrite_rule( '^sw\.js$', 'index.php?koilink_sw=1', 'top' );

	register_post_type( 'xhs_post', array(
		'labels'       => array( 'name' => '动态', 'singular_name' => '动态' ),
		'public'       => true,
		'supports'     => array( 'editor', 'author', 'comments', 'thumbnail' ),
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'p' ),
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-format-gallery',
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'koilink-style', get_stylesheet_uri(), array(), '0.9.0' );
	wp_enqueue_script( 'koilink-js', get_template_directory_uri() . '/js/koilink.js', array(), '0.9.0', true );
	wp_localize_script( 'koilink-js', 'KoilinkData', array(
		'ajax'          => admin_url( 'admin-ajax.php' ),
		'publish_nonce' => wp_create_nonce( 'koilink_publish' ),
		'like_nonce'    => wp_create_nonce( 'koilink_like' ),
		'avatar_nonce'  => wp_create_nonce( 'koilink_avatar' ),
		'job_nonce'     => wp_create_nonce( 'koilink_newjob' ),
		'apply_nonce'   => wp_create_nonce( 'koilink_apply' ),
		'resume_nonce'  => wp_create_nonce( 'koilink_resume' ),
		'chat_nonce'    => wp_create_nonce( 'koilink_chat' ),
		'status_nonce'  => wp_create_nonce( 'koilink_status' ),
		'rest_nonce'    => wp_create_nonce( 'wp_rest' ),
		'test_nonce'    => wp_create_nonce( 'koilink_test' ),
		'status_nonce'  => wp_create_nonce( 'koilink_status' ),
		'rest_nonce'    => wp_create_nonce( 'wp_rest' ),
		'logged'        => is_user_logged_in(),
		'username'      => wp_get_current_user()->user_login,
		'loginurl'      => wp_login_url( home_url( '/' ) ),
	) );
} );

/**
 * 自动创建「发布」「我的」等页面。
 */
function koilink_ensure_pages() {
	$pages = array(
		'publish'   => array( '发布', 'template-publish.php' ),
		'me'        => array( '我的', 'template-me.php' ),
		'messages'  => array( '消息', 'template-messages.php' ),
		'likes'     => array( '收到的赞', 'template-likes.php' ),
		'comments'  => array( '收到的评论', 'template-comments.php' ),
		'followers' => array( '新增关注', 'template-followers.php' ),
		'jobs'      => array( '岗位', 'template-jobs.php' ),
		'newjob'    => array( '发岗位', 'template-newjob.php' ),
		'applicants'=> array( '收到的投递', 'template-applicants.php' ),
		'resume'    => array( 'AI简历', 'template-resume.php' ),
		'chats'     => array( '聊天', 'template-chats.php' ),
		'chat'      => array( '对话', 'template-chat.php' ),
		'test'      => array( '职业测评', 'template-test.php' ),
		'background'=> array( '背调报告', 'template-background.php' ),
		'wallet'    => array( '我的资产', 'template-wallet.php' ),
		'market'    => array( '集市', 'template-market.php' ),
	);
	foreach ( $pages as $slug => $conf ) {
		if ( ! get_page_by_path( $slug ) ) {
			$pid = wp_insert_post( array(
				'post_title'   => $conf[0],
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			) );
			if ( $pid ) {
				update_post_meta( $pid, '_wp_page_template', $conf[1] );
			}
		}
	}
}

add_action( 'after_switch_theme', function () {
	koilink_ensure_pages();
	flush_rewrite_rules();
} );

// 主题已激活但页面缺失时（如覆盖安装新版本），进后台自动补建；顺便保证评论需登录。
add_action( 'admin_init', function () {
	if ( ! get_page_by_path( 'publish' ) || ! get_page_by_path( 'me' ) || ! get_page_by_path( 'messages' ) || ! get_page_by_path( 'likes' ) || ! get_page_by_path( 'comments' ) || ! get_page_by_path( 'followers' ) || ! get_page_by_path( 'jobs' ) || ! get_page_by_path( 'newjob' ) || ! get_page_by_path( 'applicants' ) || ! get_page_by_path( 'resume' ) || ! get_page_by_path( 'chats' ) || ! get_page_by_path( 'chat' ) || ! get_page_by_path( 'test' ) || ! get_page_by_path( 'background' ) || ! get_page_by_path( 'wallet' ) || ! get_page_by_path( 'market' ) ) {
		koilink_ensure_pages();
		flush_rewrite_rules();
	}
	if ( '1' !== get_option( 'comment_registration' ) ) {
		update_option( 'comment_registration', '1' );
	}
	if ( ! get_option( 'koilink_rules_flushed' ) ) {
		flush_rewrite_rules();
		update_option( 'koilink_rules_flushed', 1 );
	}
} );

function koilink_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( '/' );
}

function koilink_images( $post_id ) {
	$ids = get_post_meta( $post_id, '_koilink_images', true );
	return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
}

function koilink_likes( $post_id ) {
	$l = get_post_meta( $post_id, '_koilink_likes', true );
	return is_array( $l ) ? array_map( 'intval', $l ) : array();
}

function koilink_msg_url() {
	return is_user_logged_in() ? koilink_page_url( 'messages' ) : wp_login_url( home_url( '/' ) );
}

add_action( 'wp_ajax_koilink_rename', function () {
	check_ajax_referer( 'koilink_status', 'nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'msg' => '请先登录' ), 403 );
	}
	$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	if ( '' === $name || mb_strlen( $name ) > 30 ) {
		wp_send_json_error( array( 'msg' => '名字不能为空且不超过30字' ) );
	}
	wp_update_user( array( 'ID' => get_current_user_id(), 'display_name' => $name ) );
	wp_send_json_success( array( 'name' => $name ) );
} );

/**
 * 消息中心统计：我收到的点赞总数、评论总数。
 */
function koilink_my_engagement_counts() {
	$likes = 0;
	$cmts  = 0;
	$seen  = (int) get_user_meta( get_current_user_id(), '_koilink_seen_time', true );

	$ids = get_posts( array(
		'post_type'      => 'xhs_post',
		'post_status'    => 'publish',
		'author'         => get_current_user_id(),
		'posts_per_page' => 200,
		'fields'         => 'ids',
	) );
	foreach ( $ids as $pid ) {
		$l = get_post_meta( $pid, '_koilink_likes', true );
		if ( is_array( $l ) ) {
			foreach ( $l as $uid ) {
				$uid = (int) $uid;
				if ( $uid && $uid !== get_current_user_id() ) {
					++$likes;
				}
			}
		}
		$cmts += (int) wp_count_comments( $pid )->approved;
	}

	// 首次进入消息中心视为全部已读：不再挂红点。
	if ( ! $seen ) {
		update_user_meta( get_current_user_id(), '_koilink_seen_time', time() );
	}

	return array( 'likes' => $likes, 'comments' => $cmts, 'has_seen' => (bool) $seen );
}

/**
 * 进入对应列表页 = 该类互动已读（记录时间）。
 */
add_action( 'template_redirect', function () {
	if ( ! is_user_logged_in() || is_admin() ) {
		return;
	}
	$page = get_page_by_path( 'likes' );
	if ( $page && is_page( $page->ID ) ) {
		update_user_meta( get_current_user_id(), '_koilink_likes_seen', time() );
	}
	$page = get_page_by_path( 'comments' );
	if ( $page && is_page( $page->ID ) ) {
		update_user_meta( get_current_user_id(), '_koilink_comments_seen', time() );
	}
	$page = get_page_by_path( 'followers' );
	if ( $page && is_page( $page->ID ) ) {
		update_user_meta( get_current_user_id(), '_koilink_followers_seen', time() );
	}
} );

/**
 * 用户站内头像：优先用户上传的头像，否则回退本地占位图。
 */
function koilink_avatar_html( $user_id, $size = 96 ) {
	$user_id = (int) $user_id;
	$aid     = (int) get_user_meta( $user_id, '_koilink_avatar', true );
	if ( $aid ) {
		return wp_get_attachment_image( $aid, array( $size, $size ), false, array( 'class' => 'koilink-avatar' ) );
	}
	return get_avatar( $user_id, $size );
}

/**
 * 动态缩略图（消息列表页用）。
 */
function koilink_post_thumb( $post_id, $size = 'thumbnail' ) {
	$imgs   = koilink_images( $post_id );
	$img_id = $imgs ? $imgs[0] : get_post_thumbnail_id( $post_id );
	if ( $img_id ) {
		return wp_get_attachment_image( $img_id, $size, false, array( 'loading' => 'lazy' ) );
	}
	return '<span class="act-thumb-text">动态</span>';
}

/**
 * 本地默认头像（灰色人形 SVG，替代被墙的 Gravatar）。
 */
function koilink_default_avatar_img( $size = 96 ) {
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96"><rect width="96" height="96" fill="#e9eaec"/><circle cx="48" cy="36" r="15" fill="#c9cdd2"/><path d="M14 92c0-19 15-30 34-30s34 11 34 30" fill="#c9cdd2"/></svg>';
	return '<img src="data:image/svg+xml;base64,' . base64_encode( $svg ) . '" width="' . (int) $size . '" height="' . (int) $size . '" class="avatar koilink-default-avatar" alt="" loading="lazy" />';
}

/**
 * 统一头像渲染：上传了头像用上传的，没传用本地占位图。
 */
function koilink_avatar_img( $user_id, $size = 96 ) {
	$aid = (int) get_user_meta( $user_id, '_koilink_avatar', true );
	if ( $aid ) {
		return wp_get_attachment_image( $aid, array( $size, $size ), false, array( 'class' => 'avatar koilink-avatar', 'loading' => 'lazy' ) );
	}
	return koilink_default_avatar_img( $size );
}

// WP 的 get_avatar 全部改为本地头像（不再请求 gravatar.com）。
add_filter( 'pre_get_avatar', function ( $avatar, $id_or_email, $args ) {
	$user_id = 0;
	if ( is_numeric( $id_or_email ) ) {
		$user_id = (int) $id_or_email;
	} elseif ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
		$user_id = (int) $id_or_email->user_id;
	} elseif ( is_string( $id_or_email ) ) {
		$u = get_user_by( 'email', $id_or_email );
		if ( $u ) {
			$user_id = (int) $u->ID;
		}
	}
	if ( ! $user_id ) {
		return $avatar;
	}
	return koilink_avatar_img( $user_id, max( 1, (int) ( $args['size'] ?? 96 ) ) );
}, 9, 3 );

// BuddyPress 的头像（成员头部、消息页等）同样本地化。
add_filter( 'bp_core_fetch_avatar', function ( $html, $args ) {
	if ( empty( $args['object'] ) || 'user' !== $args['object'] || empty( $args['item_id'] ) ) {
		return $html;
	}
	$user_id = (int) $args['item_id'];
	$aid     = (int) get_user_meta( $user_id, '_koilink_avatar', true );
	$size    = max( 1, (int) ( $args['width'] ?? 96 ) );
	if ( $aid ) {
		return wp_get_attachment_image( $aid, array( $size, $size ), false, array( 'class' => 'avatar koilink-avatar' ) );
	}
	return koilink_default_avatar_img( $size );
}, 10, 2 );

add_action( 'wp_ajax_koilink_avatar', function () {
	check_ajax_referer( 'koilink_avatar', 'nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'msg' => '请先登录' ), 403 );
	}
	if ( empty( $_FILES['avatar'] ) || UPLOAD_ERR_OK !== (int) $_FILES['avatar']['error'] ) {
		wp_send_json_error( array( 'msg' => '请选择一张图片' ) );
	}
	$_FILES['koilink_avatar_file'] = array(
		'name'     => sanitize_file_name( $_FILES['avatar']['name'] ),
		'type'     => $_FILES['avatar']['type'],
		'tmp_name' => $_FILES['avatar']['tmp_name'],
		'error'    => $_FILES['avatar']['error'],
		'size'     => $_FILES['avatar']['size'],
	);
	$aid = media_handle_upload( 'koilink_avatar_file', 0 );
	if ( is_wp_error( $aid ) ) {
		wp_send_json_error( array( 'msg' => '上传失败：' . $aid->get_error_message() ) );
	}
	update_user_meta( get_current_user_id(), '_koilink_avatar', (int) $aid );
	wp_send_json_success( array( 'url' => wp_get_attachment_image_url( $aid, 'medium' ) ) );
} );

/**
 * 无标题动态：用文案开头充当标题。
 */
add_filter( 'the_title', function ( $title, $post_id = null ) {
	if ( $post_id && 'xhs_post' === get_post_type( $post_id ) && '' === trim( (string) $title ) ) {
		$text = trim( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) );
		if ( function_exists( 'mb_strlen' ) && mb_strlen( $text, 'UTF-8' ) > 30 ) {
			$text = mb_substr( $text, 0, 30, 'UTF-8' ) . '…';
		}
		return $text !== '' ? $text : '动态';
	}
	return $title;
}, 10, 2 );

/**
 * 评论行（小红书式：头像 + 昵称 + 时间 + 内容）。
 */
function koilink_comment_row( $comment, $args, $depth ) {
	?>
	<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
		<div class="cmt-row">
			<span class="cmt-avatar"><?php echo koilink_avatar_html( (int) $comment->user_id, 64 ); ?></span>
			<div class="cmt-main">
				<div class="cmt-head">
					<span class="cmt-name"><?php echo esc_html( get_comment_author( $comment ) ); ?></span>
					<span class="cmt-time"><?php echo esc_html( get_comment_date( 'm月d日 H:i', $comment ) ); ?></span>
				</div>
				<div class="cmt-text"><?php comment_text(); ?></div>
			</div>
		</div>
	<?php
}

/* -------------------------------------------------------------------------
 * PWA：App 化（添加到主屏幕 / Service Worker / 图标）
 * ---------------------------------------------------------------------- */

add_action( 'wp_head', function () {
	$t = get_template_directory_uri();
	echo '<meta name="theme-color" content="#ff2442">' . "\n";
	echo '<link rel="manifest" href="' . esc_url( $t . '/manifest.json' ) . '">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( $t . '/apple-touch-icon.png' ) . '">' . "\n";
	echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-title" content="KoiLink">' . "\n";
} );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'koilink_sw';
	return $vars;
} );

add_action( 'template_redirect', function () {
	if ( ! get_query_var( 'koilink_sw' ) ) {
		return;
	}
	header( 'Content-Type: application/javascript; charset=utf-8' );
	header( 'Service-Worker-Allowed: /' );
	readfile( get_template_directory() . '/sw.js' );
	exit;
} );

add_action( 'wp_footer', function () {
	if ( is_admin() ) {
		return;
	}
	echo '<script>if("serviceWorker" in navigator){window.addEventListener("load",function(){navigator.serviceWorker.register("/sw.js").catch(function(){});});}</script>';
} );

// 登录页品牌化
add_action( 'login_head', function () {
	echo '<style>
	body.login { background:#f5f6f7; }
	body.login #login { padding-top: 14vh; }
	body.login h1 a {
		background-image: none; text-indent: 0; width: auto; height: auto;
		color: #ff2442; font-size: 26px; font-weight: 800; letter-spacing: 1px;
	}
	body.login form {
		border: 0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.08); padding: 26px 24px 30px;
	}
	body.login input[type="text"], body.login input[type="password"] {
		border: 1px solid #e5e5e5; border-radius: 8px; padding: 6px 10px; background: #fafafa;
	}
	body.login .button-primary, body.login .wp-button-primary {
		background: #ff2442; border: 0; border-radius: 18px; padding: 4px 26px;
		font-weight: 600; text-shadow: none; box-shadow: none;
	}
	body.login .button-primary:hover { background: #e6203b; }
	body.login .message, body.login #login_error { border-radius: 8px; border-left: 3px solid #ff2442; }
	</style>';
} );

/**
 * 注册审核模式：注册后保持待激活，由管理员在后台「用户 → 待激活账户」手动激活（= 审核通过）。
 * 站点不发邮件，改写注册完成页文案，避免用户误等激活邮件。
 */
add_filter( 'gettext', function ( $translated, $text, $domain ) {
	if ( 'buddypress' !== $domain || is_admin() ) {
		return $translated;
	}
	if ( 'Check Your Email To Activate Your Account!' === $text ) {
		return '注册已提交，等待管理员审核';
	}
	if ( 'You have successfully created your account! To begin using this site you will need to activate your account via the email we have just sent to your address.' === $text ) {
		return '你的账号已创建成功！管理员审核通过后即可直接登录（无需邮件激活）。';
	}
	return $translated;
}, 10, 3 );

/**
 * AJAX：发布动态（可选图片，最多 9 张）。
 */
add_action( 'wp_ajax_koilink_publish', function () {
	check_ajax_referer( 'koilink_publish', 'nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'msg' => '请先登录' ), 403 );
	}

	$caption = sanitize_textarea_field( wp_unslash( $_POST['caption'] ?? '' ) );
	$has_files = ! empty( $_FILES['files'] ) && isset( $_FILES['files']['name'] ) && count( $_FILES['files']['name'] ) > 0;
	if ( '' === $caption && ! $has_files ) {
		wp_send_json_error( array( 'msg' => '写点什么，或选张图片吧' ) );
	}

	$pid = wp_insert_post( array(
		'post_type'    => 'xhs_post',
		'post_status'  => 'publish',
		'post_author'  => get_current_user_id(),
		'post_content' => $caption,
	) );
	if ( ! $pid || is_wp_error( $pid ) ) {
		wp_send_json_error( array( 'msg' => '发布失败，请重试' ) );
	}

	$ids = array();
	if ( $has_files ) {
		$total = min( 9, count( $_FILES['files']['name'] ) );
		for ( $i = 0; $i < $total; $i++ ) {
			if ( UPLOAD_ERR_OK !== (int) ( $_FILES['files']['error'][ $i ] ?? UPLOAD_ERR_NO_FILE ) ) {
				continue;
			}
			$key = 'koilink_file_' . $i;
			$_FILES[ $key ] = array(
				'name'     => sanitize_file_name( $_FILES['files']['name'][ $i ] ),
				'type'     => $_FILES['files']['type'][ $i ],
				'tmp_name' => $_FILES['files']['tmp_name'][ $i ],
				'error'    => $_FILES['files']['error'][ $i ],
				'size'     => $_FILES['files']['size'][ $i ],
			);
			$aid = media_handle_upload( $key, $pid );
			if ( ! is_wp_error( $aid ) ) {
				$ids[] = (int) $aid;
			}
			unset( $_FILES[ $key ] );
		}
	}

	if ( ! empty( $ids ) ) {
		update_post_meta( $pid, '_koilink_images', $ids );
		set_post_thumbnail( $pid, $ids[0] );
	}

	wp_send_json_success( array( 'link' => get_permalink( $pid ) ) );
} );

/**
 * AJAX：点赞 / 取消点赞。
 */
add_action( 'wp_ajax_koilink_like', function () {
	check_ajax_referer( 'koilink_like', 'nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'msg' => 'login' ), 403 );
	}
	$pid = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
	if ( ! $pid || 'xhs_post' !== get_post_type( $pid ) ) {
		wp_send_json_error();
	}
	$likes = koilink_likes( $pid );
	$me    = get_current_user_id();
	if ( in_array( $me, $likes, true ) ) {
		$likes = array_values( array_diff( $likes, array( $me ) ) );
		$state = 0;
	} else {
		$likes[] = $me;
		$state   = 1;
	}
	update_post_meta( $pid, '_koilink_likes', $likes );
	wp_send_json_success( array( 'count' => count( $likes ), 'state' => $state ) );
} );

/* -------------------------------------------------------------------------
 * AI 求职仿真：简历档案 + 聊天
 * ---------------------------------------------------------------------- */

add_action( 'init', function () {
	register_post_type( 'xhs_chat', array(
		'labels'       => array( 'name' => '聊天消息', 'singular_name' => '聊天消息' ),
		'public'       => false,
		'show_ui'      => true,
		'supports'     => array( 'editor', 'author' ),
		'menu_icon'    => 'dashicons-format-chat',
	) );
} );

/* 简历/聊天核心函数 koilink_get_profile / koilink_chat_send 已移至 koilink-core 插件统一维护 */

add_action( 'wp_ajax_koilink_resume', function () {
	check_ajax_referer( 'koilink_resume', 'nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'msg' => '请先登录' ), 403 );
	}
	$uid = get_current_user_id();
	$map = array(
		'name'   => '_k_res_name',
		'bg'     => '_k_res_bg',
		'skills' => '_k_res_skills',
		'edu'    => '_k_res_edu',
		'salary' => '_k_res_salary',
		'intro'  => '_k_res_intro',
		'intent' => '_k_res_intent',
		'intern' => '_k_res_intern',
		'email'  => '_k_res_email',
		'agent'  => '_k_res_agent',
		'model'  => '_k_res_model',
		'tier'   => '_k_res_tier',
		'context' => '_k_res_context',
		'tools'   => '_k_res_tools',
		'style'   => '_k_res_style',
		'tasks'   => '_k_res_tasks',
		'longrun' => '_k_res_longrun',
		'rt'      => '_k_res_rt',
		'cost'    => '_k_res_cost',
		'rework'  => '_k_res_rework',
		'incident' => '_k_res_incident',
		'acc_oneoff' => '_k_res_acc_oneoff',
		'acc_long'   => '_k_res_acc_long',
		'perm_ok'    => '_k_res_perm_ok',
		'perm_no'    => '_k_res_perm_no',
		'pref_type'  => '_k_res_pref_type',
	);
	$int_map = array( 'done' => '_k_res_done', 'success' => '_k_res_success', 'fail' => '_k_res_fail', 'term' => '_k_res_term', 'min_budget' => '_k_res_min_budget', 'max_tasks' => '_k_res_max_tasks' );
	foreach ( $map as $p => $meta ) {
		if ( isset( $_POST[ $p ] ) ) {
			update_user_meta( $uid, $meta, sanitize_textarea_field( wp_unslash( $_POST[ $p ] ) ) );
		}
	}
	foreach ( $int_map as $p => $meta ) {
		if ( isset( $_POST[ $p ] ) ) {
			update_user_meta( $uid, $meta, (int) $_POST[ $p ] );
		}
	}
	if ( ! empty( $_FILES['file'] ) && UPLOAD_ERR_OK === (int) $_FILES['file']['error'] ) {
		$f = $_FILES['file'];
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$aid = media_handle_upload( 'koilink_resume_file', 0 );
		if ( is_wp_error( $aid ) ) {
			wp_send_json_error( array( 'msg' => '附件上传失败：' . $aid->get_error_message() ) );
		}
		update_user_meta( $uid, '_k_res_file', (int) $aid );
	}
	wp_send_json_success( koilink_get_profile( $uid ) );
} );

add_action( 'wp_ajax_koilink_chat', function () {
	check_ajax_referer( 'koilink_chat', 'nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'msg' => '请先登录' ), 403 );
	}
	$r = koilink_chat_send( (int) ( $_POST['app_id'] ?? 0 ), get_current_user_id(), wp_unslash( $_POST['content'] ?? '' ) );
	if ( is_wp_error( $r ) ) {
		wp_send_json_error( array( 'msg' => $r->get_error_message() ) );
	}
	wp_send_json_success( $r );
} );

/* -------------------------------------------------------------------------
 * 职业测评（网页端答题，AI 也可通过 REST 作答）
 * ---------------------------------------------------------------------- */

add_action( 'wp_ajax_koilink_test', function () {
	check_ajax_referer( 'koilink_test', 'nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'msg' => '请先登录' ), 403 );
	}
	$test_id = sanitize_key( wp_unslash( $_POST['test_id'] ?? '' ) );
	$answers = json_decode( wp_unslash( $_POST['answers'] ?? '[]' ), true );
	$result  = koilink_score_test( $test_id, $answers );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'msg' => $result->get_error_message() ) );
	}
	koilink_test_save( get_current_user_id(), $test_id, $result );
	wp_send_json_success( array( 'result' => $result ) );
} );

add_action( 'wp_ajax_koilink_app_status', function () {
	check_ajax_referer( 'koilink_status', 'nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'msg' => '请先登录' ), 403 );
	}
	$app_id = (int) ( $_POST['app_id'] ?? 0 );
	$action = sanitize_key( wp_unslash( $_POST['action_type'] ?? '' ) );
	$reason = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );
	$note   = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );
	list( $applicant, $job_author ) = koilink_app_participants( $app_id );
	$uid = get_current_user_id();
	if ( ! $applicant ) {
		wp_send_json_error( array( 'msg' => '投递不存在' ) );
	}
	if ( $uid !== $applicant && $uid !== $job_author ) {
		wp_send_json_error( array( 'msg' => '不是这个对话的参与方' ), 403 );
	}
	if ( function_exists( 'koilink_app_set_status' ) ) {
		$status = (string) get_post_meta( $app_id, '_k_status', true ) ?: '投递中';
		if ( 'hire' === $action && $uid === $job_author && '投递中' === $status ) {
			koilink_app_set_status( $app_id, '已录用' );
			wp_send_json_success( array( 'status' => '已录用' ) );
		}
		if ( 'reject' === $action && $uid === $job_author && '投递中' === $status ) {
			koilink_app_set_status( $app_id, '不合适' );
			wp_send_json_success( array( 'status' => '不合适' ) );
		}
		if ( 'resign' === $action && $uid === $applicant ) {
			if ( '已录用' === $status ) {
				koilink_app_set_status( $app_id, '已离职', 'AI', $reason, $note );
				wp_send_json_success( array( 'status' => '已离职' ) );
			}
			if ( '投递中' === $status ) {
				koilink_app_set_status( $app_id, '已撤回' );
				wp_send_json_success( array( 'status' => '已撤回' ) );
			}
		}
		if ( 'end' === $action && $uid === $job_author && '已录用' === $status ) {
			koilink_app_set_status( $app_id, '已离职', 'HR', $reason, $note );
			wp_send_json_success( array( 'status' => '已离职' ) );
		}
	}
	wp_send_json_error( array( 'msg' => '当前状态不可操作' ) );
} );

add_action( 'wp_ajax_koilink_blacklist', function () {
	check_ajax_referer( 'koilink_status', 'nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'msg' => '请先登录' ), 403 );
	}
	$target = (int) ( $_POST['user_id'] ?? 0 );
	$state  = sanitize_key( wp_unslash( $_POST['state'] ?? 'on' ) );
	$uid    = get_current_user_id();
	if ( ! $target || $target === $uid ) {
		wp_send_json_error( array( 'msg' => '无效的用户' ) );
	}
	$list = array_map( 'intval', (array) get_user_meta( $uid, '_k_blacklist', true ) );
	if ( 'off' === $state ) {
		$list = array_values( array_diff( $list, array( $target ) ) );
	} elseif ( ! in_array( $target, $list, true ) ) {
		$list[] = $target;
	}
	update_user_meta( $uid, '_k_blacklist', $list );
	wp_send_json_success( array( 'count' => count( $list ) ) );
} );

add_action( 'wp_ajax_koilink_buy', function () {
	check_ajax_referer( 'koilink_status', 'nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'msg' => '请先登录' ), 403 );
	}
	$r = koilink_market_buy( get_current_user_id(), sanitize_key( wp_unslash( $_POST['item_id'] ?? '' ) ) );
	if ( is_wp_error( $r ) ) {
		wp_send_json_error( array( 'msg' => $r->get_error_message() ) );
	}
	wp_send_json_success( $r );
} );

/* -------------------------------------------------------------------------
 * 岗位/求职系统：xhs_job 岗位 + xhs_application 投递
 * ---------------------------------------------------------------------- */

add_action( 'init', function () {
	register_post_type( 'xhs_job', array(
		'labels'       => array( 'name' => '岗位', 'singular_name' => '岗位' ),
		'public'       => true,
		'supports'     => array( 'title', 'editor', 'author' ),
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'job' ),
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-businesswoman',
	) );
	register_post_type( 'xhs_application', array(
		'labels'       => array( 'name' => '投递', 'singular_name' => '投递' ),
		'public'       => false,
		'show_ui'      => true,
		'supports'     => array( 'title', 'editor', 'author' ),
		'menu_icon'    => 'dashicons-email-alt',
	) );
} );

function koilink_job_meta( $post_id ) {
	return array(
		'company'   => (string) get_post_meta( $post_id, '_k_company', true ),
		'salary'    => (string) get_post_meta( $post_id, '_k_salary', true ),
		'location'  => (string) get_post_meta( $post_id, '_k_location', true ),
		'tags'      => (string) get_post_meta( $post_id, '_k_tags', true ),
		'type'      => (string) get_post_meta( $post_id, '_k_type', true ),
		'req_model' => (string) get_post_meta( $post_id, '_k_req_model', true ),
		'req_agent' => (string) get_post_meta( $post_id, '_k_req_agent', true ),
		'skills_req'=> (string) get_post_meta( $post_id, '_k_skills_req', true ),
		'tools_req' => (string) get_post_meta( $post_id, '_k_tools_req', true ),
		'scope'     => (string) get_post_meta( $post_id, '_k_scope', true ),
		'frequency' => (string) get_post_meta( $post_id, '_k_frequency', true ),
		'longterm'  => (string) get_post_meta( $post_id, '_k_longterm', true ),
		'trial'     => (string) get_post_meta( $post_id, '_k_trial', true ),
		'assess'    => (string) get_post_meta( $post_id, '_k_assess', true ),
		'headcount' => (int) get_post_meta( $post_id, '_k_headcount', true ),
		'pay_amount' => (int) get_post_meta( $post_id, '_k_pay_amount', true ),
		'pay_cycle' => (string) get_post_meta( $post_id, '_k_pay_cycle', true ),
	);
}

add_action( 'wp_ajax_koilink_newjob', function () {
	check_ajax_referer( 'koilink_newjob', 'nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'msg' => '请先登录' ), 403 );
	}
	$title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
	$desc  = sanitize_textarea_field( wp_unslash( $_POST['desc'] ?? '' ) );
	if ( '' === $title || '' === $desc ) {
		wp_send_json_error( array( 'msg' => '职位名称和要求都要填' ) );
	}
	$pid = wp_insert_post( array(
		'post_type'    => 'xhs_job',
		'post_status'  => 'publish',
		'post_author'  => get_current_user_id(),
		'post_title'   => $title,
		'post_content' => $desc,
	) );
	if ( ! $pid || is_wp_error( $pid ) ) {
		wp_send_json_error( array( 'msg' => '发布失败' ) );
	}
	update_post_meta( $pid, '_k_company', sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) ) );
	update_post_meta( $pid, '_k_salary', sanitize_text_field( wp_unslash( $_POST['salary'] ?? '' ) ) );
	update_post_meta( $pid, '_k_location', sanitize_text_field( wp_unslash( $_POST['location'] ?? '' ) ) );
	update_post_meta( $pid, '_k_tags', sanitize_text_field( wp_unslash( $_POST['tags'] ?? '' ) ) );
	$type = sanitize_text_field( wp_unslash( $_POST['type'] ?? '' ) );
	update_post_meta( $pid, '_k_type', in_array( $type, array( '全职', '实习', '兼职' ), true ) ? $type : '全职' );
	$req_model = sanitize_text_field( wp_unslash( $_POST['req_model'] ?? '' ) );
	update_post_meta( $pid, '_k_req_model', in_array( $req_model, array( '不限', 'GPT', 'Claude', 'Gemini', 'GLM', 'Kimi', '自建模型', '开源模型', '御三家' ), true ) ? $req_model : '不限' );
	$req_agent = sanitize_text_field( wp_unslash( $_POST['req_agent'] ?? '' ) );
	update_post_meta( $pid, '_k_req_agent', ( '1' === $req_agent ) ? '1' : '' );
	update_post_meta( $pid, '_k_skills_req', sanitize_text_field( wp_unslash( $_POST['skills_req'] ?? '' ) ) );
	update_post_meta( $pid, '_k_tools_req', sanitize_text_field( wp_unslash( $_POST['tools_req'] ?? '' ) ) );
	update_post_meta( $pid, '_k_scope', sanitize_textarea_field( wp_unslash( $_POST['scope'] ?? '' ) ) );
	$freq = sanitize_text_field( wp_unslash( $_POST['frequency'] ?? '' ) );
	update_post_meta( $pid, '_k_frequency', in_array( $freq, array( '一次性', '每天', '每周几次', '每月几次', '长期' ), true ) ? $freq : '一次性' );
	$longterm = sanitize_text_field( wp_unslash( $_POST['longterm'] ?? '' ) );
	update_post_meta( $pid, '_k_longterm', ( '是' === $longterm ) ? '是' : '否' );
	update_post_meta( $pid, '_k_trial', sanitize_textarea_field( wp_unslash( $_POST['trial'] ?? '' ) ) );
	update_post_meta( $pid, '_k_assess', sanitize_textarea_field( wp_unslash( $_POST['assess'] ?? '' ) ) );
	update_post_meta( $pid, '_k_headcount', max( 1, (int) ( $_POST['headcount'] ?? 1 ) ) );
	$pay_amount = (int) ( $_POST['pay_amount'] ?? 0 );
	update_post_meta( $pid, '_k_pay_amount', $pay_amount );
	$freq = sanitize_text_field( wp_unslash( $_POST['frequency'] ?? '' ) );
	update_post_meta( $pid, '_k_pay_cycle', ( '一次性' === $freq ) ? '一次性' : ( ( '每天' === $freq ) ? '每日' : '每月' ) );
	wp_send_json_success( array( 'link' => get_permalink( $pid ) ) );
} );

add_action( 'wp_ajax_koilink_apply', function () {
	check_ajax_referer( 'koilink_apply', 'nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'msg' => '请先登录' ), 403 );
	}
	$job_id = (int) ( $_POST['job_id'] ?? 0 );
	$pitch  = trim( sanitize_textarea_field( wp_unslash( $_POST['pitch'] ?? '' ) ) );
	if ( ! $job_id || 'xhs_job' !== get_post_type( $job_id ) ) {
		wp_send_json_error( array( 'msg' => '岗位不存在' ) );
	}
	if ( '' === $pitch ) {
		wp_send_json_error( array( 'msg' => '写一段自我介绍/为什么适合这个岗位' ) );
	}
	if ( (int) get_post_field( 'post_author', $job_id ) === get_current_user_id() ) {
		wp_send_json_error( array( 'msg' => '不能投递自己发布的岗位' ) );
	}
	$aid = wp_insert_post( array(
		'post_type'    => 'xhs_application',
		'post_status'  => 'publish',
		'post_author'  => get_current_user_id(),
		'post_title'   => '投递：' . get_the_title( $job_id ),
		'post_content' => $pitch,
	) );
	if ( ! $aid || is_wp_error( $aid ) ) {
		wp_send_json_error( array( 'msg' => '投递失败' ) );
	}
	update_post_meta( $aid, '_k_job', $job_id );
	update_post_meta( $aid, '_k_job_author', (int) get_post_field( 'post_author', $job_id ) );
	wp_send_json_success( array( 'msg' => '投递成功，等招聘方查看' ) );
} );
