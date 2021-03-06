package DateTime;

use strict;

use SL::Util qw(_hashify);

sub new_local {
  my ($class, %params) = @_;
  return $class->new(hour => 0, minute => 0, second => 0, time_zone => $::locale->get_local_time_zone, %params);
}

sub now_local {
  return shift->now(time_zone => $::locale->get_local_time_zone);
}

sub today_local {
  return shift->now(time_zone => $::locale->get_local_time_zone)->truncate(to => 'day');
}

sub to_kivitendo_time {
  my ($self, %params) = _hashify(1, @_);
  return $::locale->format_date_object_to_time($self, %params);
}

sub to_kivitendo {
  my ($self, %params) = _hashify(1, @_);
  return $::locale->format_date_object($self, %params);
}

sub to_lxoffice {
  # Legacy name.
  goto &to_kivitendo;
}

sub from_kivitendo {
  return $::locale->parse_date_to_object($_[1]);
}

sub from_lxoffice {
  # Legacy name.
  goto &from_kivitendo;
}

sub add_business_duration {
  my ($self, %params) = @_;

  my $abs_days = abs $params{days};
  my $neg      = $params{days} < 0;
  my $bweek    = $params{businessweek} || 5;
  my $weeks    = int ($abs_days / $bweek);
  my $days     = $abs_days % $bweek;

  if ($neg) {
    $self->subtract(weeks => $weeks);
    $self->add(days => 8 - $self->day_of_week) if $self->day_of_week > $bweek;
    $self->subtract(days => $self->day_of_week > $days ? $days : $days + (7 - $bweek));
  } else {
    $self->add(weeks => $weeks);
    $self->subtract(days => $self->day_of_week - $bweek) if $self->day_of_week > $bweek;
    $self->add(days => $self->day_of_week + $days <= $bweek ? $days : $days + (7 - $bweek));
  }

  $self;
}

sub add_businessdays {
  my ($self, %params) = @_;

  $self->add_business_duration(%params);
}

sub subtract_businessdays {
  my ($self, %params) = @_;

  $params{days} *= -1;

  $self->add_business_duration(%params);
}

1;

__END__

=encoding utf8

=head1 NAME

SL::Helpers::DateTime - helper functions for L<DateTime>

=head1 FUNCTIONS

=over 4

=item C<new_local %params>

Returns the time given in C<%params> with the time zone set to the
local time zone.

=item C<now_local>

Returns the current time with the time zone set to the local time zone.

=item C<today_local>

Returns the current date with the time zone set to the local time zone.

=item C<to_kivitendo %param>

Formats the date and time according to the current kivitendo user's
date format with L<Locale::format_datetime_object>.

The legacy name C<to_lxoffice> is still supported.

=item C<from_kivitendo $string>

Parses a date string formatted in the current kivitendo user's date
format and returns an instance of L<DateTime>.

Note that only dates can be parsed at the moment, not the time
component (as opposed to L<to_kivitendo>).

The legacy name C<from_lxoffice> is still supported.

=back

=head1 AUTHOR

Moritz Bunkus E<lt>m.bunkus@linet-services.deE<gt>

=cut
