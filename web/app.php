<?php

use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;

$loader = require_once dirname($_SERVER['SCRIPT_FILENAME']).'/../app/bootstrap.php.cache';

// Use APC for autoloading to improve performance.
// Change 'sf2' to a unique prefix in order to prevent cache key conflicts
// with other applications also using APC.
/*
$apcLoader = new ApcClassLoader('sf2', $loader);
$loader->unregister();
$apcLoader->register(true);
*/

require_once dirname($_SERVER['SCRIPT_FILENAME']).'/../app/AppKernel.php';
//require_once dirname($_SERVER['SCRIPT_FILENAME'])'/../app/AppCache.php';

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
$request = Request::createFromGlobals();

// NOTE: This is added so we set any ip making a request to Akeneo as a trusted
// proxy. The Akeneo machine is setup to only accept connections from our
// network of machines.
Request::setTrustedProxies([$request->server->get('REMOTE_ADDR')]);

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
