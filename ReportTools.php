<?php
/**
 * @package ReportTools
 * @version 0.3
 * @author Mike Rodarte
 *
 * This file relies on PDOWrapper.php, helpers.php, and config.php. 
 * See https://github.com/mts7/PDOWrapper for the first 2 files.
 * config.php has define('NL', "\n"); in it, so if you have no config file, 
 * you'll need to add that statement in here or the main plugin file.
 */

/**
 * Handle all database reporting for WordPress
 *
 * @package ReportTools
 * @author Mike Rodarte
 */
class ReportTools {
    /** @var PDOWrapper Database object*/
    private $db = null;

    /** @var string Email to use for sending reports */
    private $adminEmail = 'admin@example.com';

    /** @var string Last error message logged */
    private $lastMessage = '';

    
    /**
     * Start the database connection
     * @uses ReportTools::$db
     * @uses PDOWrapper::database()
     * @uses PDOWrapper::begin()
     * @see options.php
     */
    public function __construct()
    {
        require_once 'PDOWrapper.php';
        $this->db = new PDOWrapper();
        // use DB_NAME constant from wp_config.php
        $this->db->database(DB_NAME);
        $this->db->begin();

        // get admin email from options
        $email = get_option('rt-setting');
        if (is_string_ne($email)) {
            $this->adminEmail = $email;
        }
    }


    /**
     * Get the admin email address
     * @todo This will eventually use the Wordpress Settings API, but is not currently.
     * @return string Admin email address
     */
    public function getAdmin()
    {
        return $this->adminEmail;
    }


    /**
     * Get the last error message set.
     * @return string Last error message
     */
    public function getMessage( )
    {
        return $this->lastMessage;
    }


    /**
     * Get users from the Wordpress database in CSV format and email to the admin.
     * @return bool Success or failure of the process
     * @uses PDOWrapper::select()
     * @uses ReportTools::buildCsv()
     * @uses ReportTools::sendEmail()
     */
    public function getUsers()
    {
        global $wpdb;
        
        // NL is a constant from the config.php file
        $q = 'SELECT u.user_email AS "Email", u.user_login AS "username"'.NL;
        $q .= 'FROM '.$wpdb->users.' u'.NL;

        // get rows from database for query
        $rows = $this->db->select($q);

        // stop processing if there were no rows found
        if (!is_array_ne($rows)) {
            $this->log('no data found. '.$this->db->lastError());
            return false;
        }

        // build CSV string with the array
        $csv = $this->buildCsv($rows);

        // save the CSV file
        $path = plugin_dir_path( __FILE__ );
        $name = 'users-report_'.date('Y-m-d').'.csv';
        $file_name = $path.$name;
        $bytes = file_put_contents($file_name, $csv);

        // check to see if the file was written or not
        if (!$bytes) {
            $this->log('error writing files');
            return false;
        }

        // send email to the admin with the CSV file as an attachment
        $sent = $this->sendEmail('User Report from '.date('Y-m-d'), 'Users from Wordpress database', $file_name);

        // delete the file from the file system so email addresses remain private
        unlink($file_name);

        return $sent;
    }


    /**
     * Build a CSV string based on an associative array
     * @param array $array Associative array of values
     * @return string CSV
     */
    private function buildCsv($array) {
        // if the array passed is not an array or is empty, stop processing
        if (!is_array_ne($array)) {
            $this->log('array is not valid');
            return '';
        }

        // put the keys from the associative array in the first line of the CSV string
        $csv = implode(',', array_keys($array[0])).NL;
        // go through all rows in the array, adding each one to the CSV string
        foreach ($array as $row) {
            $csv .= implode(',', $row).NL;
        }

        return $csv;
    }


    /**
     * Save the message in the last message member with the date stamp
     * @todo Add the Log class functionality
     * @param string $msg Message to log
     * @uses ReportTools::$lastMessage
     */
    private function log($msg)
    {
        // DATE_FORMAT is Y-m-d H:i:s and is defined in helpers.php
        $this->lastMessage = date(DATE_FORMAT).' - '.$msg;
    }


    /**
     * Send email with attachment to admin email address
     * @param string $subject Subject of the email
     * @param string $message Plain-text message for the email
     * @param string $attachment Full path of attachment file
     * @return bool PHPMailer sent
     * @uses ReportTools::$adminEmail
     * @uses PHPMailer::send()
     */
    private function sendEmail($subject = '', $message = '', $attachment = '') {
        // require WordPress' PHPMailer class from the wp-includes directory
        require( ABSPATH . WPINC . '/class-phpmailer.php' );
        // create new instance of PHPMailer; WordPress might have a better solution for this
        $mail = new PHPMailer();
        // only continue processing if $mail is an object
        if (!is_object($mail)) {
            $this->log('PHPMailer does not seem to exist; please include the file.');
            return false;
        }

        // add the admin email address to PHPMailer
        $added = $mail->addAddress($this->adminEmail);

        // there is a chance PHPMailer did not like the address, so check for that
        if (!$added) {
            $this->log('could not add admin email address to mail');
            return false;
        }

        // from is the plugin and the host
        $mail->setFrom('report_tools@'.$_SERVER['HTTP_HOST'], 'Report Tools');
        // set the subject and body from the arguments
        $mail->Subject = $subject;
        $mail->Body = $message;
        // check for file attachment
        if (is_file($attachment)) {
            // add the attachment only if it is an actual file
            $mail->addAttachment($attachment);
        }
        // send email
        $sent = $mail->send();

        return $sent;
    }
}
?>