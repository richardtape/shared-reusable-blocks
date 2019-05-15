<?php

$i = new AcceptanceTester( $scenario );
$i->wantTo( 'use Chrome for acceptance tests' );

$i->havePostInDatabase( [
	'post_title'  => 'Hello World!',
	'post_status' => 'publish',
] );

$i->amOnPage( '/' );

$i->see( 'Hello World!' );

$i->amOnPage( '/hello-world/' );

$i->see( 'then start writing' );
