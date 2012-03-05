<?php
/**
 * Base class for PayPal IPN handler. You should extend this class and overwrite
 * the methods you need/want. Most important is _statusCompleted() which is called
 * after a successful payment. You should also implement _checkEmail() to ensure
 * the receiver of the money is one of your email-addresses.
 *
 * You should not change this class, extend this class and put your custom things
 * in there.
 *
 * See App_PayPalIPN example class, you can use your database there, whatever is
 * needed to process the paypal notification.
 *
 * Usage:
 *
 * $paypalIpn = new YourOwnPayPalIPN();
 * $paypalIpn->processIPN($_POST);
 *
 *
 * @author Michael Kliewe
 * @copyright 2012 Michael Kliewe
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link http://www.phpgangsta.de/
 */

abstract class PHPGangsta_PayPalIPN
{
    /**
     * Path to logfile, false if no logging should be done
     *
     * @var bool
     */
    protected $_logFile    = false;
    /**
     * Set this to true if PayPal sandbox should be used
     *
     * @var bool
     */
    protected $_useSandbox = true;
    /**
     * Internal property to hold all POST data we got from PayPal
     *
     * @var array
     */
    protected $_postData   = array();

    /**
     * Set the log file for output, if you set this, information
     * will be sent to the log file.
     *
     * @param string|bool $filename
     * @return PHPGangsta_PayPalIPN
     */
    public function setLogFile($filename = false)
    {
        $this->_logFile = $filename;
        return $this;
    }

    /**
     * Logs a message.
     *
     * Override this method to be notified of Errors,Info or Debug messages.
     * (Can also use log_file() to simply store them in a file some place)
     *
     * @param string $message Text of message to be logged.
     */
    protected function _writeLog($message)
    {
        if (!$this->_logFile) {
            return;
        }

        file_put_contents($this->_logFile, date('Y.m.d H:i:s ') . $message."\n", FILE_APPEND);
    }

    /**
     * Main method that will call all other methods
     *
     * @param array $postData
     */
    public function processIPN(array $postData)
    {
        $this->_postData = $postData;

        $this->_writeLog('Processing PayPal IPN');

        $paypalResponse = $this->_callPayPal();

        $this->_writeLog('Response: [' . $paypalResponse . ']');
        if (strcmp($paypalResponse, 'VERIFIED') == 0) {
            $this->_writeLog('Paypal has verified the data');

            // Make sure the receiver email address is one of yours, the transaction id is correct and the
            // amount of money is correct
            $this->_checkEmail();
            $this->_checkTxnId();
            $this->_checkAmount();

            switch ($postData['payment_status']) {
                case 'Completed':
                    $this->_statusCompleted();
                    break;
                case 'Canceled_Reversal':
                    $this->_statusCancelReverse();
                    break;
                case 'Denied':
                    $this->_statusDenied();
                    break;
                case 'Failed':
                    $this->_statusFailed();
                    break;
                case 'Pending':
                    $this->_statusPending();
                    break;
                case 'Refunded':
                    $this->_statusRefunded();
                    break;
                case 'Reversed':
                    $this->_statusReversed();
                    break;
                default:
                    throw new Exception('Unknown status from Paypal: ' . $postData['payment_status']);
            }
        } else if (strcmp($paypalResponse, 'INVALID') == 0) {
            $this->_writeLog('Invalid response: ' . $paypalResponse);
            throw new Exception('Invalid response: ' . $paypalResponse);
        }

        $this->_writeLog('Finished processing PayPal IPN');
    }

    /**
     * Call to PayPal servers, via curl or fsockopen() and return result string
     *
     * @return string
     */
    protected function _callPayPal()
    {
        // read the post from PayPal system and add 'cmd'
        foreach ($this->_postData as $key => $value) {
            $this->_writeLog("Variable: $key=[$value]");
        }

        if (function_exists('curl_init')) {
            return $this->_callPayPalCurl();
        } else {
            return $this->_callPayPalFsockOpen();
        }
    }

    /**
     * This method is called to verify the data against PayPal servers via curl.
     *
     * @return string
     * @throws Exception
     */
    protected function _callPayPalCurl()
    {
        $postData = $this->_postData;
        $postData['cmd'] = '_notify-validate';

        $ch = curl_init();
        if ($this->_useSandbox) {
            curl_setopt($ch, CURLOPT_URL, "https://www.sandbox.paypal.com/cgi-bin/webscr");
        } else {
            curl_setopt($ch, CURLOPT_URL, "https://www.paypal.com/cgi-bin/webscr");
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $output = curl_exec($ch);

        curl_close($ch);

        if ($output === false) {
            throw new Exception('PayPay server not reachable!');
        }

        return $output;
    }

    /**
     * This method is called to verify the data against PayPal servers via fsockopen().
     *
     * @return string
     * @throws Exception
     */
    protected function _callPayPalFsockOpen()
    {
        $postData = $this->_postData;
        $postData['cmd'] = '_notify-validate';

        $query = http_build_query($postData);

        // post back to PayPal system to validate
        $header  = "POST /cgi-bin/webscr HTTP/1.0\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($query) . "\r\n\r\n";

        if ($this->_useSandbox) {
            $fp = fsockopen('ssl://www.sandbox.paypal.com', 443, $errno, $errstr, 30);
        } else {
            $fp = fsockopen('ssl://www.paypal.com', 443, $errno, $errstr, 30);
        }

        if (!$fp) {
            throw new Exception('PayPay server not reachable!');
        }

        $output = '';
        fputs($fp, $header . $query);
        while(!feof($fp)) {
            $output .= fgets($fp, 1024);
        }
        fclose($fp);

        // strip off headers
        $output = substr($output, strpos($output, "\r\n\r\n") + 4);

        return $output;
    }

    /**
     * Transaction/Payment completed.
     *
     * This is typically the most important method you'll need to override to perform
     * some sort of action when a successful transaction has been completed.
     *
     * You could override the other status's (such as reverse or denied) to
     * reverse whatever was done, but that could interfere if you're denying a
     * payment or refunding someone for a good reason. In those cases, it's
     * probably best to simply do whatever steps are required manually.
     */
    protected function _statusCompleted()
    {
        $this->_writeLog('Not doing anything in statusCompleted');
    }

    /**
     * Pending state
     * Look at $this->_postData['pending_reason']
     */
    protected function _statusPending()
    {
        $this->_writeLog('Not doing anything in _statusPending: ' . $this->_postData['pending_reason']);
    }

    /**
     * Cancel Reverse
     */
    protected function _statusCancelReverse()
    {
        $this->_writeLog('Not doing anything in _statusCancelReverse');
    }

    /**
     * Merchant denied payment.
     */
    protected function _statusDenied()
    {
        $this->_writeLog('Not doing anything in _statusDenied');
    }

    /**
     * Transaction failed.
     */
    protected function _statusFailed()
    {
        $this->_writeLog('Not doing anything in _statusFailed');
    }

    /**
     * Merchant refunded payment.
     */
    protected function _statusRefunded()
    {
        $this->_writeLog('Not doing anything in _statusRefunded');
    }

    /**
     * Charges reversed.
     * Look at $this->_postData['reason_code']
     */
    protected function _statusReversed()
    {
        $this->_writeLog('Not doing anything in _statusReversed ' . $this->_postData['reason_code']);
    }

    /**
     * Check that the amount/currency is correct for item_id.
     * You should override this method to ensure the amount is correct.
     * Throw an Exception if data is invalid or other things go wrong.
     *
     * $this->_postData['item_number'] = The item number
     * $this->_postData['mc_gross']    = The amount being paid
     * $this->_postData['mc_currency'] = Currency code of amount
     */
    protected function _checkAmount()
    {
        $this->_writeLog('Not doing _checkAmount(' . $this->_postData['mc_gross'] . ', ' . $this->_postData['mc_currency'] . ')');
    }

    /**
     * Check txnId has not already been used.
     * Override this method to ensure txnId is not a duplicate.
     * Throw an Exception if data is invalid or other things go wrong.
     *
     * $this->_postData['txn_id'] = The transaction ID from paypal.
     */
    protected function _checkTxnId()
    {
        $this->_writeLog('Not doing _checkTxnId(' . $this->_postData['txn_id'] . ')');
    }

    /**
     * Check email address for validity.
     * Override this method to make sure you are the one being paid.
     * Throw an Exception if data is invalid or other things go wrong.
     *
     * $this->_postData['receiver_email'] = The email who is about to receive payment.
     */
    protected function _checkEmail()
    {
        $this->_writeLog('Not doing _checkEmail(' . $this->_postData['receiver_email'] . ')');
    }
}