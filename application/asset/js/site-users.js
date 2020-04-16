$(document).ready(function() {
    var permissionsTable = $('#site-user-permissions');
    var existingRows = permissionsTable.data('existing-rows');
    var index = 0;

    var updateRowIndex = function(rowId) {
      var rowInput = $('.resource-id[value="' + rowId + '"]');
      var row = rowInput.parents('.resource-row');
      row.find('[name*="o:site_permission[__index__]"]').each(function() {
        var inputName = $(this).attr('name');
        var newinputName = inputName.replace('__index__', index);
        $(this).attr('name', newinputName);
      });
      index++;
    };

    $.each(existingRows, function() {
      var selectedRole = this.role;
      var existingRowValueInput = $('.resource-id[value="' + this.id + '"]');
      var existingRow = existingRowValueInput.parents('.resource-row');
      existingRow.find('select').val(selectedRole);
      updateRowIndex(this.id);
    });
    
    permissionsTable.on('appendRow', function() {
      updateRowIndex($('[name="o:site_permission[__index__][o:user][o:id]"]').val());
    });
});

