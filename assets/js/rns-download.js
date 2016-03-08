(function() {
  var $ = jQuery;

  $(function() {
    $('#rns-transmissions-create-ap-file').find(':button, :submit').on('click.edit-post', function(event) {
      var $button = $(this);

      if ($button.hasClass('disabled')) {
        event.preventDefault();
        return;
      }

      if ($button.hasClass('submitdelete') || $button.is('#post-preview'))
        return;

      $('form#post').off('submit.edit-post').on('submit.edit-post', function(event) {
        if (event.isDefaultPrevented())
          return;

        // Stop autosave
        if (wp.autosave)
          wp.autosave.server.suspend();

        $(window).off('beforeunload.edit-post');
      });
    });
  });
})();
