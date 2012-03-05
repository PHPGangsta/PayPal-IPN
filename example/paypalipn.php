<?php

/**
 * This is an example using the PayPalIPN class
 *
 *
 * @author Michael Kliewe
 * @copyright 2012 Michael Kliewe
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link http://www.phpgangsta.de/
 */

require_once '../App/PayPalIPN.php';
$paypalIpn = new App_PayPalIPN();
$paypalIpn->setLogFile('../paypalipn.log')  // make sure this is outside the document root
    ->processIPN($_POST);