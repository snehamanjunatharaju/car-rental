(function ($) {
  "use strict";

  // Mobile Navigation
  if ($('.main-nav').length) {
    var $mobile_nav = $('.main-nav').clone().prop({
      class: 'mobile-nav d-lg-none',
      id: 'mobile-navigation'
    });
    $mobile_nav.find('[id]').each(function() {
      var originalId = $(this).attr('id');
      var mobileId = 'mobile-' + originalId;
      $(this).attr('id', mobileId);
      $mobile_nav.find('[aria-labelledby="' + originalId + '"]').attr('aria-labelledby', mobileId);
    });
    $mobile_nav.find('[data-toggle="dropdown"]').attr('data-display', 'static');
    $mobile_nav.attr('aria-hidden', 'true').attr('inert', '');
    $('body').append($mobile_nav);
    $('body').prepend('<button type="button" class="mobile-nav-toggle d-lg-none" aria-label="Open navigation" aria-controls="mobile-navigation" aria-expanded="false"><i class="fa fa-bars" aria-hidden="true"></i></button>');
    $('body').append('<div class="mobile-nav-overly"></div>');

    $(document).on('click', '.mobile-nav-toggle', function(e) {
      e.stopPropagation();
      var $toggle = $('.mobile-nav-toggle');
      var $nav = $('.mobile-nav');

      if ($('body').hasClass('mobile-nav-active')) {
        closeMobileNav(true);
        return;
      }

      $('body').addClass('mobile-nav-active');
      $toggle.attr('aria-expanded', 'true').attr('aria-label', 'Close navigation');
      $toggle.find('i').removeClass('fa-bars').addClass('fa-times');
      $nav.removeAttr('inert').attr('aria-hidden', 'false');
      $('.mobile-nav-overly').show();
      $nav.find('a').first().trigger('focus');
    });

    $(document).on('click', '.mobile-nav-overly', function() {
      closeMobileNav(true);
    });

    $(document).on('keydown', function(e) {
      if (e.key === 'Escape' && $('body').hasClass('mobile-nav-active')) {
        closeMobileNav(true);
      }
    });
    
    $(document).on('click', '.mobile-nav .drop-down > a', function(e) {
      e.preventDefault();
      $(this).next().slideToggle(300);
      $(this).parent().toggleClass('active');
    });

    $(document).click(function(e) {
      var container = $(".mobile-nav, .mobile-nav-toggle");
      if (!container.is(e.target) && container.has(e.target).length === 0) {
        closeMobileNav(false);
      }
    });
  } else if ($(".mobile-nav, .mobile-nav-toggle").length) {
    $(".mobile-nav, .mobile-nav-toggle").hide();
  }

  function closeMobileNav(restoreFocus) {
    if (!$('body').hasClass('mobile-nav-active')) {
      return;
    }

    var $toggle = $('.mobile-nav-toggle');
    $('body').removeClass('mobile-nav-active');
    $toggle.attr('aria-expanded', 'false').attr('aria-label', 'Open navigation');
    $toggle.find('i').removeClass('fa-times').addClass('fa-bars');
    $('.mobile-nav').attr('aria-hidden', 'true').attr('inert', '');
    $('.mobile-nav-overly').hide();

    if (restoreFocus) {
      $toggle.trigger('focus');
    }
  }

})(jQuery);
