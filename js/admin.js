jQuery(function ($) {
	$('#wc_autoship_import_muenster_file_drop').dropandpop(
		WC_Autoship_Import_Muenster.ajax_url + '?action=wc_autoship_import_muenster_file',
		function (response, xhr) {
			var $result = $('#wc_autoship_import_muenster_file_result');
			var result = '';
			try {
				result = JSON.parse(response);
			} catch( e ) {
				$result.text('Error parsing result');
				return;
			}
			$result.html('');
			if (result.error) {
				$result.text(result.error);
			} else {
				$(result).each(function (r, row) {
					var $row = $('<div class="wc-autoship-import-muenster-row"></div>');
					$row.append($('<div class="wc-autoship-import-muenster-row-count"></div>').text('Row Count: ' + r));
					for (var name in row.data) {
						var $field = $('<span class="wc-autoship-import-muenster-field"></span>');
						$field.text(name + ': ' + row.data[name]);
						$row.append($field);
					}
					if (row.error) {
						var $error = $('<div class="wc-autoship-import-muenster-error"></div>');
						$error.text('Error: ' + row.error);
						$row.append($error);
					}
					$result.append($row);
				});
			}
			$result.show();
		},
		function (files, e) {
			$('#wc_autoship_import_muenster_file_result').text('Loading... Please wait...').show();
		},
		function (files, e) {
			$('#wc_autoship_import_muenster_file_result').text('Loading... Please wait...').show();
		}
	);
});