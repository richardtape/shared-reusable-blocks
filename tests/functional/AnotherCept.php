<?php

$i = new AcceptanceTester( $scenario );
$i->wantTo( 'Testing clicking about' );

$i->amOnPage( '/' );

$i->click( 'Log in' );

$i->amOnPage( '/wp-login.php' );
