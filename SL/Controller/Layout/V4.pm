package SL::Controller::Layout::V4;

use strict;
use parent qw(SL::Controller::Layout::Base);
use SL::Controller::Layout::Css;
use SL::Controller::Layout::Top;

use URI;

sub new {
  my ($class, @slurp) = @_;

  my $self = $class->SUPER::new(@slurp);
  $self->{top} = SL::Controller::Layout::Top->new;
  $self;
}

sub pre_content {
  $_[0]{top}->render .
  &render;
}

sub stylesheets {
  $_[0]{top}->stylesheets
}

sub start_content {
  "<div id='content'>\n";
}

sub end_content {
  "</div>\n";
}

sub render {
  my ($self) = @_;

  $self->{sub_class} = 1;

  my $callback            = $::form->unescape($::form->{callback});
  $callback               = URI->new($callback)->rel($callback) if $callback;
  $callback               = "login.pl?action=company_logo"      if $callback =~ /^(\.\/)?$/;

  $self->SUPER::render('menu/menuv4', { no_menu => 1, no_output => 1 },
    force_ul_width => 1,
    date           => $self->clock_line,
    menu           => $self->print_menu,
    callback       => $callback,
  );
}

1;