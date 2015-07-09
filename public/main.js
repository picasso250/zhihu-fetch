$(function () {
	$.fn.extend({button: function(state) {
		var $button = $(this);
		if (state === 'loading') {
			$button.attr('disable', true);
			var old = $button.text();
			$button.text('loading');
			$button.data('old', old)
		}
		if (state === 'reset') {
			$button.attr('disable', true);
			$button.text($button.data('old'));
		}
	}})
	var postForm = $('form[ajax]').on('submit', function (e) {
		e.preventDefault();
		var $this = $(this);
		var alert = $this.find('.return-msg');
		var $btn = $(this).find('button[type=submit]');
		$btn.button('loading');
		$.post($this.attr('action'), $this.serialize(), function (ret) {
			if (ret.code === 0) {
				var id = ret.data.id;
				var check_task = function () {
					$.get('/check_task/'+id, {}, function (ret) {
						if (ret.code !== 0) {
							setTimeout(check_task, 2000);
						};
						alert.text(ret.msg);
					});
				};
				check_task();
			}
			$btn.button('reset');
			alert.removeClass('alert-hidden').text(ret.msg);
		}, 'json');
	});
});
