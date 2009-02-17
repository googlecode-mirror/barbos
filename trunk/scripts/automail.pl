#!/usr/bin/perl
#######################################################################
#    automail.pl
#
#    Perl script used for remote email piping...same as as the PHP version.
#
#    Peter Rotich <peter@osticket.com>
#    Copyright (c) 2006,2007 osTicket
#    http://www.osticket.com
#
#    Released under the GNU General Public License WITHOUT ANY WARRANTY.
#    See LICENSE.TXT for details.
#
#    vim: expandtab sw=4 ts=4 sts=4:
#    $Id: $
#######################################################################

#Configuration: Enter the url and key. That is it.
#  url=> URL to pipe.php e.g http://yourdomain.com/support/api/pipe.php 
#  key=> API's pass phrase
%config = (url => 'http://yourdomain.com/osticket_dir/api/pipe.php',
           key => 'pass phrase here');

#Get piped message from stdin
while (<STDIN>) {
    $rawemail .= $_;
}

use Digest::MD5 qw(md5_hex);
use LWP::UserAgent;
$ua = LWP::UserAgent->new;

$ua->agent(md5_hex($config{'key'}));
$ua->timeout(30);

use HTTP::Request::Common qw(POST);

my $enc ='text/plain';
my $req = (POST $config{'url'}, Content_Type => $enc,Content => $rawemail);
$response = $ua->request($req);

#
#Process response
# Depending on your MTA add the exit codes 
#
if($response->is_success and $response->code==200 and length($response->content)==1) {
#print "Success";
}else {
#print "Error ".$response->content;
}
exit;
