<?php
/**
 * Example to load plugin container and see if it works properly.
 * 
 * All callable $plugin properties will be executed after running $plugin->run().
 * Deferred services will be run after accessing. i.e $plugin['mailer'].
 */

// Load PluginContainer class
require("PluginContainer.php");

/** @var PluginContainer $plugin Contain properties and plugin services */
$plugin = new PluginContainer();

// Plugin version
$plugin['version'] = '1.0.0';

// Load service for admin side
$plugin['admin_side'] = function() {
	// Example function to load something on wp-admin side
	echo 'Admin side object loaded <br />';
};

// Define a deferred service with sugar syntax. It won't be executed until it be accessed
$plugin['*mailer'] = function() {
	// Only executed when directly accessed
	echo 'Mailer service loaded. <br />';
};

// Run plugin
$plugin->run();

// Access to 'mailer' service
$plugin['mailer'];
