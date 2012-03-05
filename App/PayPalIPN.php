<?php

/**
 * @author Michael Kliewe
 * @copyright 2012 Michael Kliewe
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link http://www.phpgangsta.de/
 */

require_once dirname(__FILE__) . '/../PHPGangsta/PayPalIPN.php';

class App_PayPalIPN extends PHPGangsta_PayPalIPN
{
    protected $_useSandbox = true;  // set this to false if you want to run this in Paypal Live Environment

    protected function _checkEmail() {
        $validEmailAddresses = array(
            'seller_1321807401_biz@tg-tg.de',
            'paypal@phpgangsta.de',
            'payment@myshop.de',
        );

        // we check if the receiver email address we got from paypal is one of our email addresses.
        if (!in_array($this->_postData['receiver_email'], $validEmailAddresses)) {
            $this->_writeLog('Invalid receiver email address: ' . $this->_postData['receiver_email']);
            throw new Exception('Invalid receiver email address: ' . $this->_postData['receiver_email']);
        }

        $this->_writeLog('Valid receiver email address found: ' . $this->_postData['receiver_email']);
    }

    protected function _statusCompleted()
    {
        $this->_writeLog('Status completed, writing to database now');

        // Here you can now set the status of the order in the database, send a mail to the customer
        // or whatever is needed in this situation.
        // You can use all variables from $this->_postData array.

    }
}