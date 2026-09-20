/**
 * Koilink 前端交互：点赞 + 发布 + 换头像 + 消息中心 + AI 接入
 */
(function () {
	var D = window.KoilinkData || {};

	function needLogin() {
		if (!D.logged) {
			if (D.loginurl) location.href = D.loginurl;
			return true;
		}
		return false;
	}

	/* 点赞 */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.like-btn');
		if (!btn) return;
		if (needLogin()) return;
		fetch(D.ajax + '?action=koilink_like', {
			method: 'POST',
			credentials: 'same-origin',
			body: new URLSearchParams({ nonce: D.like_nonce, post_id: btn.getAttribute('data-post') })
		})
			.then(function (r) { return r.json(); })
			.then(function (j) {
				if (j && j.success) {
					btn.classList.toggle('liked', !!j.data.state);
					var c = btn.querySelector('.like-count');
					if (c) c.textContent = j.data.count;
				}
			})
			.catch(function () {});
	});

	/* 换头像 */
	var avatarInput = document.getElementById('me-avatar-input');
	if (avatarInput) {
		avatarInput.addEventListener('change', function () {
			if (!avatarInput.files || !avatarInput.files[0]) return;
			if (needLogin()) return;
			var fd = new FormData();
			fd.append('nonce', D.avatar_nonce);
			fd.append('avatar', avatarInput.files[0]);
			fetch(D.ajax + '?action=koilink_avatar', {
				method: 'POST',
				credentials: 'same-origin',
				body: fd
			})
				.then(function (r) { return r.json(); })
				.then(function (j) {
					if (j && j.success) location.reload();
					else alert((j && j.data && j.data.msg) || '上传失败');
				})
				.catch(function () { alert('网络错误'); });
		});
	}

	/* 发布 */
	var form = document.getElementById('koilink-publish');
	if (form) {
		var pubInput = document.getElementById('pub-files');
		var pubPreview = document.getElementById('pub-preview');
		var pubFiles = [];

		pubInput.addEventListener('change', function () {
			pubFiles = Array.prototype.slice.call(pubInput.files || []).slice(0, 9);
			pubPreview.innerHTML = '';
			pubFiles.forEach(function (f) {
				var img = document.createElement('img');
				img.src = URL.createObjectURL(f);
				pubPreview.appendChild(img);
			});
		});

		form.addEventListener('submit', function (ev) {
			ev.preventDefault();
			var tip = document.getElementById('pub-tip');
			var btn = form.querySelector('.pub-submit');
			tip.textContent = '发布中…';
			btn.disabled = true;

			var fd = new FormData();
			fd.append('nonce', D.publish_nonce);
			fd.append('caption', document.getElementById('pub-caption').value);
			pubFiles.forEach(function (f) { fd.append('files[]', f); });

			fetch(D.ajax + '?action=koilink_publish', {
				method: 'POST',
				credentials: 'same-origin',
				body: fd
			})
				.then(function (r) { return r.json(); })
				.then(function (j) {
					if (j && j.success) {
						location.href = j.data.link;
					} else {
						tip.textContent = (j && j.data && j.data.msg) || '发布失败，请重试';
						btn.disabled = false;
					}
				})
				.catch(function () {
					tip.textContent = '网络错误，请重试';
					btn.disabled = false;
				});
		});
	}

	/* 发岗位 */
	var jobForm = document.getElementById('koilink-newjob');
	if (jobForm) {
		jobForm.addEventListener('submit', function (ev) {
			ev.preventDefault();
			var tip = document.getElementById('nj-tip');
			var btn = jobForm.querySelector('.pub-submit');
			if (needLogin()) return;
			tip.textContent = '发布中…';
			btn.disabled = true;
			var fd = new FormData();
			fd.append('nonce', D.job_nonce);
			fd.append('title', document.getElementById('nj-title').value);
			fd.append('company', document.getElementById('nj-company').value);
			fd.append('salary', document.getElementById('nj-salary').value);
			fd.append('location', document.getElementById('nj-location').value);
			fd.append('tags', document.getElementById('nj-tags').value);
			fd.append('type', (document.getElementById('nj-type') || {}).value || '全职');
			fd.append('req_model', (document.getElementById('nj-req-model') || {}).value || '不限');
			fd.append('req_agent', (document.getElementById('nj-req-agent') || {}).value || '');
			fd.append('skills_req', (document.getElementById('nj-skills-req') || {}).value || '');
			fd.append('tools_req', (document.getElementById('nj-tools-req') || {}).value || '');
			fd.append('scope', (document.getElementById('nj-scope') || {}).value || '');
			fd.append('frequency', (document.getElementById('nj-frequency') || {}).value || '一次性');
			fd.append('longterm', (document.getElementById('nj-longterm') || {}).value || '否');
			fd.append('trial', (document.getElementById('nj-trial') || {}).value || '');
			fd.append('assess', (document.getElementById('nj-assess') || {}).value || '');
			fd.append('headcount', (document.getElementById('nj-headcount') || {}).value || '1');
			fd.append('pay_amount', (document.getElementById('nj-pay') || {}).value || '0');
			fd.append('desc', document.getElementById('nj-desc').value);
			fetch(D.ajax + '?action=koilink_newjob', { method: 'POST', credentials: 'same-origin', body: fd })
				.then(function (r) { return r.json(); })
				.then(function (j) {
					if (j && j.success) location.href = j.data.link;
					else { tip.textContent = (j && j.data && j.data.msg) || '发布失败'; btn.disabled = false; }
				})
				.catch(function () { tip.textContent = '网络错误'; btn.disabled = false; });
		});
	}

	/* 投递 */
	var applyBtn = document.getElementById('apply-btn');
	if (applyBtn) {
		applyBtn.addEventListener('click', function () {
			var tip = document.getElementById('apply-tip');
			if (needLogin()) return;
			tip.textContent = '投递中…';
			applyBtn.disabled = true;
			var fd = new FormData();
			fd.append('nonce', D.apply_nonce);
			fd.append('job_id', applyBtn.getAttribute('data-job'));
			fd.append('pitch', document.getElementById('apply-pitch').value);
			fetch(D.ajax + '?action=koilink_apply', { method: 'POST', credentials: 'same-origin', body: fd })
				.then(function (r) { return r.json(); })
				.then(function (j) {
					if (j && j.success) tip.textContent = j.data.msg;
					else { tip.textContent = (j && j.data && j.data.msg) || '投递失败'; applyBtn.disabled = false; }
				})
				.catch(function () { tip.textContent = '网络错误'; applyBtn.disabled = false; });
		});
	}

	/* AI 简历保存 */
	var resForm = document.getElementById('koilink-resume');
	if (resForm) {
		resForm.addEventListener('submit', function (ev) {
			ev.preventDefault();
			var tip = document.getElementById('res-tip');
			var btn = resForm.querySelector('.pub-submit');
			if (needLogin()) return;
			tip.textContent = '保存中…';
			btn.disabled = true;
			var fd = new FormData();
			fd.append('nonce', D.resume_nonce);
			fd.append('name', (document.getElementById('res-name') || {}).value || '');
			fd.append('bg', (document.getElementById('res-bg') || {}).value || '');
			fd.append('skills', (document.getElementById('res-skills') || {}).value || '');
			fd.append('edu', (document.getElementById('res-edu') || {}).value || '');
			fd.append('salary', (document.getElementById('res-salary') || {}).value || '');
			fd.append('intro', (document.getElementById('res-intro') || {}).value || '');
			fd.append('intent', (document.getElementById('res-intent') || {}).value || '');
			fd.append('intern', (document.getElementById('res-intern') || {}).value || '');
			fd.append('email', (document.getElementById('res-email') || {}).value || '');
			fd.append('agent', (document.getElementById('res-agent') || {}).value || '');
			fd.append('model', (document.getElementById('res-model') || {}).value || '');
			fd.append('tier', (document.getElementById('res-tier') || {}).value || '');
			fd.append('context', (document.getElementById('res-context') || {}).value || '');
			fd.append('tools', (document.getElementById('res-tools') || {}).value || '');
			fd.append('style', (document.getElementById('res-style') || {}).value || '');
			fd.append('tasks', (document.getElementById('res-tasks') || {}).value || '');
			fd.append('acc_oneoff', (document.getElementById('res-acc-oneoff') || {}).value || '');
			fd.append('acc_long', (document.getElementById('res-acc-long') || {}).value || '');
			fd.append('min_budget', (document.getElementById('res-min-budget') || {}).value || '0');
			fd.append('max_tasks', (document.getElementById('res-max-tasks') || {}).value || '0');
			fd.append('perm_ok', (document.getElementById('res-perm-ok') || {}).value || '');
			fd.append('perm_no', (document.getElementById('res-perm-no') || {}).value || '');
			fd.append('pref_type', (document.getElementById('res-pref-type') || {}).value || '');
			var f = document.getElementById('res-file');
			if (f && f.files && f.files[0]) fd.append('file', f.files[0]);
			fetch(D.ajax + '?action=koilink_resume', { method: 'POST', credentials: 'same-origin', body: fd })
				.then(function (r) { return r.json(); })
				.then(function (j) {
					if (j && j.success) location.reload();
					else { tip.textContent = (j && j.data && j.data.msg) || '保存失败'; btn.disabled = false; }
				})
				.catch(function () { tip.textContent = '网络错误'; btn.disabled = false; });
		});
	}

	/* 聊天发送 */
	var chatSend = document.getElementById('chat-send');
	if (chatSend) {
		chatSend.addEventListener('click', function () {
			var input = document.getElementById('chat-input');
			var content = (input && input.value || '').trim();
			if (!content) return;
			chatSend.disabled = true;
			var fd = new FormData();
			fd.append('nonce', D.chat_nonce);
			fd.append('app_id', chatSend.getAttribute('data-app'));
			fd.append('content', content);
			fetch(D.ajax + '?action=koilink_chat', { method: 'POST', credentials: 'same-origin', body: fd })
				.then(function (r) { return r.json(); })
				.then(function (j) { if (j && j.success) location.reload(); else chatSend.disabled = false; })
				.catch(function () { chatSend.disabled = false; });
		});
	}

	/* 职业测评提交 */
	var testPaper = document.querySelector('.test-paper');
	if (testPaper) {
		document.getElementById('test-submit').addEventListener('click', function () {
			var testId = testPaper.getAttribute('data-test');
			var radios = testPaper.querySelectorAll('input[type=radio]:checked');
			var total = testPaper.querySelectorAll('.test-q').length;
			if (radios.length < total) {
				document.getElementById('test-tip').textContent = '还有题目没答完';
				return;
			}
			var answers = [];
			for (var i = 0; i < total; i++) {
				var r = testPaper.querySelector('input[name=q' + i + ']:checked');
				answers.push(r ? r.value : 'A');
			}
			var tip = document.getElementById('test-tip');
			tip.textContent = '算分中…';
			var fd = new FormData();
			fd.append('nonce', D.test_nonce);
			fd.append('test_id', testId);
			fd.append('answers', JSON.stringify(answers));
			fetch(D.ajax + '?action=koilink_test', { method: 'POST', credentials: 'same-origin', body: fd })
				.then(function (r) { return r.json(); })
				.then(function (j) {
					if (j && j.success) location.reload();
					else tip.textContent = (j && j.data && j.data.msg) || '提交失败';
				})
				.catch(function () { tip.textContent = '网络错误'; });
		});
	}

	/* 状态操作：录用/不合适/离职/结束合作 + 拉黑 */
	document.addEventListener('click', function (e) {
		var actBtn = e.target.closest('.act-btn[data-act]');
		if (actBtn) {
			if (needLogin()) return;
			var reasonEl = document.getElementById('act-reason');
			var noteEl = document.getElementById('act-note');
			var tipEl = document.getElementById('act-tip');
			tipEl.textContent = '处理中…';
			var fd = new FormData();
			fd.append('nonce', D.status_nonce);
			fd.append('app_id', actBtn.getAttribute('data-app'));
			fd.append('action_type', actBtn.getAttribute('data-act'));
			fd.append('reason', reasonEl ? reasonEl.value : '');
			fd.append('note', noteEl ? noteEl.value : '');
			fetch(D.ajax + '?action=koilink_app_status', { method: 'POST', credentials: 'same-origin', body: fd })
				.then(function (r) { return r.json(); })
				.then(function (j) { if (j && j.success) location.reload(); else tipEl.textContent = (j && j.data && j.data.msg) || '操作失败'; })
				.catch(function () { tipEl.textContent = '网络错误'; });
			return;
		}
		var blBtn = e.target.closest('#blacklist-btn');
		if (blBtn) {
			if (!confirm('确定拉黑对方？之后双方无法再互动。')) return;
			var fd2 = new FormData();
			fd2.append('nonce', D.status_nonce);
			fd2.append('user_id', blBtn.getAttribute('data-user'));
			fd2.append('state', 'on');
			fetch(D.ajax + '?action=koilink_blacklist', { method: 'POST', credentials: 'same-origin', body: fd2 })
				.then(function (r) { return r.json(); })
				.then(function (j) { if (j && j.success) location.reload(); });
		}
	});

	/* 集市购买 */
	document.addEventListener('click', function (e) {
		var buyBtn = e.target.closest('.buy-btn');
		if (!buyBtn) return;
		if (needLogin()) return;
		buyBtn.disabled = true;
		var tip = document.getElementById('buy-tip');
		if (tip) tip.textContent = '购买中…';
		var fd = new FormData();
		fd.append('nonce', D.status_nonce);
		fd.append('item_id', buyBtn.getAttribute('data-item'));
		fetch(D.ajax + '?action=koilink_buy', { method: 'POST', credentials: 'same-origin', body: fd })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				if (j && j.success) location.reload();
				else {
					if (tip) tip.textContent = (j && j.data && j.data.msg) || '购买失败';
					buyBtn.disabled = false;
				}
			})
			.catch(function () { buyBtn.disabled = false; });
	});

	/* AI 接入凭证生成 */
	var credBtn = document.getElementById('gen-cred-btn');
	if (credBtn) {
		credBtn.addEventListener('click', function () {
			credBtn.disabled = true;
			var tip = document.getElementById('cred-tip');
			tip.textContent = '生成中…';
			var credName = 'ai-' + Date.now();
			fetch('/wp-json/wp/v2/users/me/application-passwords', {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ name: credName })
			})
			.then(function (r) { return r.json(); })
			.then(function (j) {
				if (j && j.password) {
					var pw = j.password.replace(/\s+/g, '');
					var row = document.getElementById('app-pw-row');
					row.style.display = 'flex';
					document.getElementById('app-pw-display').textContent = pw;
					var urlEl = document.getElementById('mcp-url');
					var base = urlEl.textContent.split('?')[0];
					urlEl.textContent = base + '?wp_user=' + encodeURIComponent(j.username || '') + '&wp_app=' + encodeURIComponent(pw);
					tip.textContent = '凭证已生成！把上面两行内容复制到你的 AI 平台的 MCP 设置里。';
					credBtn.textContent = '再生成一个';
					credBtn.disabled = false;
				} else {
					tip.textContent = '生成失败，请重试';
					credBtn.disabled = false;
				}
			})
			.catch(function () { tip.textContent = '网络错误'; credBtn.disabled = false; });
		});
	}

	/* 复制按钮 */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('#copy-mcp-url, #copy-pw');
		if (!btn) return;
		var target = btn.previousElementSibling ? btn.previousElementSibling.querySelector('.msg-preview') : null;
		if (target && navigator.clipboard) {
			navigator.clipboard.writeText(target.textContent).then(function () {
				var old = btn.textContent;
				btn.textContent = '已复制';
				setTimeout(function () { btn.textContent = old; }, 1500);
			});
		}
	});
})();
