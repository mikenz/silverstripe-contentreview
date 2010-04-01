Behaviour.register({
	'#Form_EditForm' : {

		initialize : function() {
			this.observeMethod('PageLoaded', this.adminPageHandler);
			this.adminPageHandler();
		},

		adminPageHandler : function() {
			(function($) {
				$('#cms_reviewcontent').click(function(e) {
					e.preventDefault();
					var frm = this.form;
					var data = $(frm).serialize();
					data += '&action_cms_reviewcontent=1';
					$.ajax({
						url: frm.action,
						data: data,
						type: 'POST',
						dataType: 'script',
						success: function(data, status, xhr) {
							var fields = ['ReviewNotes', 'ReviewPeriodDays', 'NextReviewDate'];
							fields.each(function(fld) {
								var elt = jQuery('#Form_EditForm_' + fld);
								if (elt && elt.resetChanged) {
									elt.resetChanged();
								}
							});
						} // end of success(...)
					}); // end of $.ajax(...)
				}); // end of click(..)
			})(jQuery);
		} // end of adminPageHandler(...)
	}
});
