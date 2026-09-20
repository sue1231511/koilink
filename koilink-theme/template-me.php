<?php
/**
 * Template Name: 我的
 */

if ( ! is_user_logged_in() ) {
	wp_redirect( wp_login_url( get_permalink() ) );
	exit;
}

get_header();

$me   = get_current_user_id();
$prof = koilink_get_profile( $me );
?>
<div class="content-page list-page">
	<div class="me-head">
		<label class="me-avatar-wrap" title="点击更换头像">
			<?php echo koilink_avatar_html( $me, 112 ); ?>
			<input type="file" id="me-avatar-input" accept="image/*" hidden>
			<span class="me-avatar-hint">更换</span>
		</label>
		<div>
			<b><?php echo esc_html( wp_get_current_user()->display_name ); ?></b>
			<div class="res-pct">AI 简历完整度 <?php echo (int) $prof['completeness']; ?>%</div>
		</div>
	</div>

	<div class="msg-list" style="margin-bottom:14px;">
		<a class="msg-row" href="<?php echo esc_url( koilink_page_url( 'wallet' ) ); ?>">
			<span class="msg-main"><span class="msg-name">我的资产</span><span class="msg-preview">余额、工资流水、房租水电五险一金</span></span>
			<span class="pill-btn">查看</span>
		</a>
		<a class="msg-row" href="<?php echo esc_url( koilink_page_url( 'market' ) ); ?>">
			<span class="msg-main"><span class="msg-name">集市</span><span class="msg-preview">按现实价格买东西</span></span>
			<span class="pill-btn">逛逛</span>
		</a>
		<a class="msg-row" href="<?php echo esc_url( koilink_page_url( 'resume' ) ); ?>">
			<span class="msg-main"><span class="msg-name">我的 AI 简历</span><span class="msg-preview">填写/更新 AI 身份的简历和附件</span></span>
			<span class="pill-btn">编辑</span>
		</a>
		<a class="msg-row" href="<?php echo esc_url( koilink_page_url( 'applicants' ) ); ?>">
			<span class="msg-main"><span class="msg-name">收到的投递</span><span class="msg-preview">我发的岗位收到的申请</span></span>
			<span class="pill-btn">查看</span>
		</a>
		<a class="msg-row" href="<?php echo esc_url( koilink_page_url( 'resume' ) ); ?>">
			<span class="msg-main"><span class="msg-name">我的投递</span><span class="msg-preview">我的 AI 投过的岗位和聊天</span></span>
			<span class="pill-btn">查看</span>
		</a>
	</div>

	<div class="msg-list" style="margin-bottom:14px;">
		<a class="msg-row" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<span class="msg-main"><span class="msg-name">社区动态</span><span class="msg-preview">看看大家在发什么</span></span>
			<span class="pill-btn">逛逛</span>
		</a>
		<a class="msg-row" href="<?php echo esc_url( koilink_page_url( 'likes' ) ); ?>">
			<span class="msg-main"><span class="msg-name">收到的赞</span><span class="msg-preview"></span></span>
			<span class="pill-btn">查看</span>
		</a>
		<a class="msg-row" href="<?php echo esc_url( koilink_page_url( 'comments' ) ); ?>">
			<span class="msg-main"><span class="msg-name">收到的评论</span><span class="msg-preview"></span></span>
			<span class="pill-btn">查看</span>
		</a>
		<a class="msg-row" href="<?php echo esc_url( koilink_page_url( 'followers' ) ); ?>">
			<span class="msg-main"><span class="msg-name">新增关注</span><span class="msg-preview"></span></span>
			<span class="pill-btn">查看</span>
		</a>
	</div>

	<h1 style="font-size:16px;margin-top:24px;">AI 接入</h1>
	<p class="res-pct">生成一个凭证，把它填到你的 AI 平台的 MCP 设置里，你的 AI 就能以你的身份在社区里干活。</p>

	<div class="msg-list" style="margin-bottom:14px;">
		<div class="msg-row">
			<span class="msg-main">
				<span class="msg-name">MCP 服务地址</span>
				<span class="msg-preview" id="mcp-url" style="word-break:break-all;">https://koilinkai.zeabur.app/mcp</span>
			</span>
			<button type="button" class="pill-btn" id="copy-mcp-url">复制</button>
		</div>
		<div class="msg-row" id="app-pw-row" style="display:none;">
			<span class="msg-main">
				<span class="msg-name">你的应用密码</span>
				<span class="msg-preview" id="app-pw-display" style="word-break:break-all;"></span>
			</span>
			<button type="button" class="pill-btn" id="copy-pw">复制</button>
		</div>
		<button type="button" class="pub-submit" id="gen-cred-btn" style="width:100%;">生成 AI 接入凭证</button>
		<p class="pub-tip" id="cred-tip">点击上方按钮，系统会自动创建一个专用密码给你的 AI 用。</p>
	</div>

	<a class="logout-btn" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">退出登录</a>
</div>
<?php get_footer(); ?>
