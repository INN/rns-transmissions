(function() {
  var $ = jQuery,
      connections_view,
      transmissions,
      transmission;

  // Models, colections
  var Transmission = Backbone.Model.extend({
    idAttribute: 'ID',

    url: function() {
      if (typeof this.get('ID') == 'undefined') {
        return 'transmission';
      } else {
        return 'transmission/' + this.get('ID');
      }
    }
  });

  var Transmissions = Backbone.Collection.extend({
    model: Transmission,

    url: function() {
      return 'transmissions';
    },

    add_by_id: function(post_id) {
      var self = this;

      if (!Boolean(this.get(post_id))) {
        var post = new Transmission({ ID: post_id })

        post.fetch({
          success: function() {
            self.add(post)
          }
        });

        return true;
      }
    }
  });

  // Views
  var ConnectionsView = Backbone.View.extend({
    events: {
      'click .rns-connection-add a': 'selectPosts',
      'click .rns-remove-connection': 'removeConnection',
      'click .rns-available-connection': 'addConnection',
      'input :input': 'search',
      'keypress :input': 'search'
    },

    initialize: function() {
      this.$el.find('.rns-connections').sortable({
        handle: '.sortable-handle',
        stop: this.sortStop.bind(this),
        forceHelperSize: true
      });
      return Backbone.View.prototype.initialize.apply(this, arguments);
    },

    search: _.debounce(function(event) {
      var self = this,
          target = $(event.currentTarget),
          search = new Transmissions();

      self.showSpinner();
      search.fetch({
        search_term: target.val(),
        success: function() {
          self.available = search;
          self.renderAvailable();
          self.hideSpinner();
        }
      });

      return false;
    }, 500),

    sortStop: function(event, ui) {
      var item =  ui.item,
          post = this.collection.get(item.data('id')),
          newIdx = item.parent().find('> div').index(item);

      this.collection.remove(post);
      this.collection.add(post, {at: newIdx});
      this.saveTransmission();
    },

    removeConnection: function(event) {
      var target = $(event.currentTarget).parent(),
          idx = target.parent().find('> div').index(target),
          post = this.collection.at(idx);

      this.collection.remove(post);
      this.saveTransmission();
      return false;
    },

    addConnection: function(event) {
      var target = $(event.currentTarget).parent(),
          idx = target.parent().find('> div').index(target),
          post = this.available.at(idx);

      this.collection.add(post);
      this.available.remove(post);
      this.saveTransmission();
      return false;
    },

    saveTransmission: function() {
      var self = this,
          connected_ids = this.collection.map(function(m) { return m.get('ID'); }),
          post_meta = this.model.get('post_meta');

      post_meta.rns_transmissions_connected_posts = connected_ids;
      this.showSpinner();
      this.model.set({ post_meta: post_meta });
      this.model.save(this.model.toJSON(), {
        success: function() {
          self.render();
          self.hideSpinner.bind(this);
        }
      });
    },

    render: function() {
      this.renderConnected();
      this.hideSpinner();
      return this;
    },

    selectPosts: function() {
      this.$el.find('.rns-create-connections').show();
      this.showSpinner();
      this.available = new Transmissions();
      this.available.fetch({
        success: this.renderAvailable.bind(this)
      });
      return false;
    },

    renderAvailable: function() {
      var self = this,
          tmpl = _.template($('#rns-connection-add-item-tmpl').html());

      self.$el.find('.rns-available-connections').html('');
      self.available.each(function(model) {
        self.$el.find('.rns-available-connections').append(tmpl(model.toJSON()));
      });

      self.hideSpinner();
    },

    renderConnected: function() {
      if (!this.collection.length)
        return;

      var self = this,
          tmpl = _.template($('#rns-connection-item-tmpl').html());

      this.$el.find('.rns-connections').html('');
      this.collection.each(function(model) {
        self.$el.find('.rns-connections').append(tmpl(model.toJSON()));
      });
    },

    showSpinner: function() {
      this.$el.find('.spinner').css({display: 'inline-block', visibility: 'visible'});
    },

    hideSpinner: function() {
      this.$el.find('.spinner').css({display: 'none', visibility: 'hidden'});
    }
  });

  // Utilities
  var do_ajax = function(url, data, success, error) {
    var json, params;

    json = JSON.stringify(data);

    params = {
      url: ajaxurl,
      method: 'POST',
      data: {
        action: 'rns_transmissions_ajax',
        path: url,
        data: json
      },
      dataType: 'json',
      success: function(data, textStatus, jqXHR) {
        if (success) {
          return success(data, textStatus, jqXHR);
        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        if (error) {
          return error(jqXHR, textStatus, errorThrown);
        }
      }
    };

    return $.ajax(params);
  };

  Backbone.sync = function(method, model, options) {
    var data, error, success, url;
    data = method === 'create' || method === 'update' ? model.toJSON() : options;
    url = method !== 'read' ? "" + (model.url()) + "/" + method : model.url();
    success = options.success;
    error = options.error;
    var xhr = do_ajax(url, data, success, error);
    model.trigger('request', model, xhr, options);
    return xhr;
  };

  $(document).ready(function() {
    transmission = new Transmission({ ID: $('#post_ID').val() });
    transmissions = new Transmissions(null, { comparator: false });

    connections_view = new ConnectionsView({
      model: transmission,
      collection: transmissions,
      el: '#rns-connections-view'
    }).render();

    connections_view.collection.on('reset', connections_view.render.bind(connections_view));

    var load_connections = function(model) {
      var post_meta = model.get('post_meta'),
          connected_ids = ((typeof post_meta.rns_transmissions_connected_posts !== 'undefined') ?
                           post_meta.rns_transmissions_connected_posts : []);

      transmissions.fetch({
        post_ids: connected_ids,
        reset: false
      });
    };

    transmission.fetch({
      success: load_connections
    });
  });
})();
