(function() {
  var $ = jQuery;

  $(function() {
    var groups = $('#rns-default-lists-groups');

    groups.find('> label > input').on('change', function() {
      // Deselect all the nested groups
      $('.nested-group').find('input').removeAttr('checked');
      // Find the parent el of the input that was clicked
      var parent = $(this).parent();
          first_group = $(parent.find('.nested-group input')[0]);
      // Select the first group
      first_group.attr('checked', 'checked');
    });

    groups.find('.nested-group input').on('change', function() {
      var nested_group = $(this).closest('.nested-group');
          list_input = nested_group.parent().find('> input');

      // Select the corresponding list radio
      list_input.attr('checked', 'checked');
    });
  });
})();
