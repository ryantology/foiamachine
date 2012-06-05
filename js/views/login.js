var LoginView = Backbone.View.extend({

  initialize: function() {
    this.template = _.template(tpl.get('login'));
  },

  events: {
    'click #login a.login': 'login',
    'keypress #login input': 'loginEnter',
    'click #register a.register': 'register',
    'keypress #register input': 'registerEnter'
  },

  render: function() {
    $(this.el).addClass('span12').html(this.template());
    return this
  },

  alert: function(success, message) {
    var alertView = new Backbone.View();
    alertView.template = _.template($('#alert-template').html());
    $(alertView.el).html(alertView.template());

    if (success) {
      alertView.$('.alert').addClass('alert-success');
    } else {
      alertView.$('.alert').addClass('alert-error');
    }
    alertView.$('.message').html(message);

    $('#alert-container').html(alertView.el);
  },

  login: function(e) {
    e.preventDefault();

    if ($('#login .username input').val().length == 0) {
      this.alert(false, 'Username cannot be blank.');
      return;
    }
    if ($('#login .password input').val().length == 0) {
      this.alert(false, 'Password cannot be blank.');
      return;
    }

    this.model.set({
      username: $('#login .username input').val(),
      password: $('#login .password input').val()
    });
    var self = this;
    this.model.login({
      good: function() {
        app.navigate('dash', {trigger: true});
      },
      bad: function(errstr) {
        self.alert(false, errstr);
      }
    });
  },

  loginEnter: function(e) {
    if (e.keyCode == 13) this.login(e);
  },

  register: function (e) {
    e.preventDefault();

    if ($('#register .username input').val().length == 0) {
      this.alert(false, 'Username cannot be blank.');
      return;
    }
    if ($('#register .email input').val().length == 0) {
      this.alert(false, 'Email cannot be blank.');
      return;
    }
    if ($('#register .password1 input').val().length > 8) {
      this.alert(false, 'Passwords must be at least 8 characters.');
      return;
    }
    if ($('#register .password1 input').val() !=
        $('#register .password2 input').val()) {
      this.alert(false, 'The passwords do not match.');
      return;
    }
    if ($('#register .code input').val().length == 0) {
      this.alert(false, 'You must have an access code to register.');
      return;
    }

    var self = this;
    this.model.set({
      username: $('#register .username input').val(),
      password: $('#register .password1 input').val(),
      email: $('#register .email input').val(),
      code: $('#register .code input').val()
    });
    this.model.register({
      good: function() {
        app.navigate('dash', {trigger: true});
      },
      bad: function(errstr) {
        self.alert(false, errstr);
      }
    });
  },

  registerEnter: function (e) {
    if (e.keyCode == 13) this.register(e);
  }

});
