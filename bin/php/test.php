#!/usr/bin/env php
<?php
/**
 * @package nxcBMWTest
 * @author  Serhey Dolgushev <serhey.dolgushev@nxc.no>
 * @date    27 Jun 2011
 **/

require 'autoload.php';

$cli = eZCLI::instance();
$cli->setUseStyles( true );

$scriptSettings = array();
$scriptSettings['description'] = 'NXC BMW Test';
$scriptSettings['use-session'] = true;
$scriptSettings['use-modules'] = true;
$scriptSettings['use-extensions'] = true;
$scriptSettings['site-access'] = 'siteadmin';

$script = eZScript::instance( $scriptSettings );
$script->startup();
$script->initialize();
$options = $script->getOptions(
	'[test_method][iteration_size][times]',
	array(
		'test_method'    => 'Method which will be called in the test CLASS::METHOD',
		'iteration_size' => 'Test iteration size (for debuging)',
		'times'          => 'How many times test method should be called'
	)
);

if( count( $options['arguments'] ) < 3 ) {
    $cli->error( 'You should specify all arguments' );
    $script->shutdown( 1 );
}

$ini           = eZINI::instance();
$userCreatorID = $ini->variable( 'UserSettings', 'UserCreatorID' );
$user          = eZUser::fetch( $userCreatorID );
if( ( $user instanceof eZUser ) === false ) {
    $cli->error( 'Cannot get user object by userID = "' . $userCreatorID . '". ( See site.ini [UserSettings].UserCreatorID )' );
    $script->shutdown( 1 );
}
eZUser::setCurrentlyLoggedInUser( $user, $userCreatorID );

$startTime = microtime( true );

$testCallback  = explode( '::', $options['arguments'][0] );
$iterationSize = (int) $options['arguments'][1];
$times         = (int) $options['arguments'][2];

$test = new $testCallback[0];
if( is_callable( array( $test, $testCallback[1] ) ) === false ) {
    $cli->error( 'Method "' . $testCallback[0] . '::' . $testCallback[1] . '" isn`t callable' );
    $script->shutdown( 1 );
}

for( $i = 1; $i <= $times; ++$i ) {
	$test->$testCallback[1]();

	if( $i % $iterationSize === 0 ) {
		$executionTime = round( microtime( true ) - $startTime, 2 );
		$cli->output(
			'Iteration #' . $i / $iterationSize . ' (' . $i . '/' . $times .
			'). Execution time ' . $executionTime . ' secs.'
		);
	}
}

$executionTime = round( microtime( true ) - $startTime, 2 );
$cli->output( 'Test took ' . $executionTime . ' secs.' );
$cli->output( 'AVG Test execution time: ' . $executionTime / $times );

unset( $test );
$script->shutdown( 0 );
?>
