<?php

/*
 * Email Parser Class
 *
 * Handles plaintext emails
 * Parses headers, message body and attachments
 */

class emailParser {
    /**
     * Will store the raw email content passed to the constructor
     */
    private $rawContent;
    
    /**
     * An array to hold all the email's headers
     *	
     *  Has all lowercase keys
     *  Will attempt to store repetative headers in an an numbered array
     */
    private $emailHeaders;
    
    /**
     * The still encoded message body of the email
     */
    private $emailMessage;
    
    /**
     * A numerical array of all an email's attachments
     *  
     *  Attachments are stored as an emailParser, to allow recursion
     */
    private $emailAttachments;
    
    /**
     * A helper array that will store individual lines of the as we proccess them
     */
    private $emailLines;

    /**
     * The constructor
     *  
     *  Input:  A string containing the entire email
     */
    public function emailParser($raw) {
        // Set default value states
        $this->rawContent = $raw;
        $this->emailHeaders = Array();
        $this->emailAttachments = Array();
        $this->emailMessage = '';
        
        // Standardize email's lines
        $this->normalizeLineEndings();
        $this->splitLines();
        $this->cleanArrayEdges();
        
        // Parse the entire email
        $this->parseEmailHeaders();
        $this->parseEmailMessage();
    }
    
#pragma mark -----------------------------------------------------------------
#pragma mark Parsing Functions

    /**
     * Parses the email's headers into the header array
     *  
     *  Will loop through each line, until a double blank occurs
     *  (The message body will start after that)
     */
    private function parseEmailHeaders() {
        // Vars used in the function
        $line = '';
        $matches = Array();
        $lastHeaderValue;
        
        // While there are still lines in the array, go!
        while (sizeof($this->emailLines)) {
            $line = array_shift($this->emailLines);

            if ($line == '') {                                                  // Check for a double blank line
                                                                                    // If yes, the message body starts
                break;
            } else {                                                            // Else, treat the line as a header
                if ($this->startsWithLetter($line)) {                           // If the line starts with a letter,
                                                                                    // it is the start of a new header
                    preg_match('/([^:]+):\s*(.*)$/', $line, $matches);
                    $key = strtolower($matches[1]);
                    if (!array_key_exists($key, $this->emailHeaders)) {         // Check to see if a header with this key exists
                                                                                // If not, add this one.
                        $this->emailHeaders[$key] = $matches[2];
                        //This will set a reference var, so we can edit it later
                        $lastHeaderValue = &$this->emailHeaders[$key];
                    } else {                                                    // Else, try to make a new array to hold the headers
                        if (!is_array($this->emailHeaders[$key])) {
                            $temp = Array($this->emailHeaders[$key]);
                        } else {
                            $temp = $this->emailHeaders[$key];
                        }
                        $i = array_push($temp, $matches[2]);
                        $this->emailHeaders[$key] = $temp;
                        //This will set a reference var, so we can edit it later
                        $lastHeaderValue = &$this->emailHeaders[$key][$i - 1];
                    }
                } else {                                                        // If the line doesn't start with a letter
                                                                                    // Then it is a continuation of the last header
                    // Use the reference var to append this continuation
                    $lastHeaderValue .= preg_replace('/^\s+/', ' ', $line);
                }
            }
        }
    }
    
    /**
     * Parses the message body of the email
     *  
     *  Will also catch any attachments in the process
     */
    private function parseEmailMessage() {
        $contentType = $this->getHeader('content-type');
        if (strpos($contentType, 'boundary') !== false) {                       // Check to see if this message has multiple parts (eg. attachments)
            $this->parseMultiPartMessage();
        } else {                                                                // Else, think of it as a single message.
            $this->emailMessage = implode("\n", $this->emailLines);
            $this->emailLines = Array();
        }
    }
    
    /**
     * Parses the multiple parts of an email
     *  
     *  (When there are attachments, the email will be broken up into multiple pieces)
     */
    private function parseMultiPartMessage() {
        // Find the "boundary" that separates the pieces
        $boundary = preg_replace('/[^=]+=(.+)/', "$1", $this->getHeader('content-type'));
        $boundary = preg_replace('/^"?([^"]+)"?$/', "$1", $boundary);

        // Split the message into pices
        $pattern = '/(--)?' . $boundary . '(--)?/';
        $messages = preg_split($pattern, $this->rawContent, NULL, PREG_SPLIT_NO_EMPTY);

        // Process each piece, except for the header piece ($i = 0)
        for ($i = 1; $i < sizeof($messages); ++$i) {
            $message = $messages[$i];
            $this->parseMessage($message);
        }
    }
    
    /**
     * Parses each individual piece of a multipart messages
     *  
     *  Input:  The piece we are currently working on
     */
    private function parseMessage($message) {
        // Treat each piece as it's own email to allow recursion
        $email = new emailParser($message);
        
        // Determine the what type of piece this is
        $contentType = $email->getHeader('content-type');
        $type = substr($contentType, 0, strpos($contentType, '/'));
        switch ($type) {
            case 'text':                                                        // If this piece is text, it may be the message body
                if (strpos($contentType, 'attachment') === false) {                 // If this doesn't mention 'attachment', than it is the body
                    // Set the main email's message var
                    $this->emailMessage = $email->emailMessage;
                    // Add to the main email's headers
                    foreach ($email->getHeaders() as $key => $value) {
                        $this->setHeader($key, $value);
                    }
                    break;
                }                                                               //Else, treat it like an attachment (eg. a .txt file)
            case 'image':
            case 'application':
                // Push all attachments into the attachments array.
                array_push($this->emailAttachments, $email);
        }
    }
    
#pragma mark -----------------------------------------------------------------
#pragma mark Helper Functions

    /**
     * Converts all the email's CR and CRLF line endings into LF line endings
     *  
     *  (It's the unix way...)
     */
    private function normalizeLineEndings() {
        $this->rawContent = preg_replace('/\r\n?/', "\n", $this->rawContent);
    }
    
    /**
     * Splits each line of the raw email string into an array
     */
    private function splitLines() {
        $this->emailLines = explode("\n", $this->rawContent);
    }

    /**
     * Remove the blank lines that proceed and trail the actual content of an email
     */
    private function cleanArrayEdges() {
        $line = '';
        // Originally, I used a while loop, but it produced bad results
        // This will loop through each line from the beginning
        for ($i = 0; $i < sizeof($this->emailLines); ++$i) {
            $line = $this->emailLines[$i];
            if (preg_match('/[^\w]/i', $line)) {                                // If this line contains a non-whitespace char, we need to stop.
                break;
            } else {                                                            // Else, remove this line from the array
                array_shift($this->emailLines);
                // Make sure to decrement $i,
                // or else we would skip the actual next line
                --$i;
            }
        }
        // This will loop through each line from the end
        for ($i = sizeof($this->emailLines) - 1; $i >= 0 ; --$i) {
            $line = $this->emailLines[$i];
            if (preg_match('/[a-z]/i', $line)) {                                // If this line contains a non-whitespace char, we need to stop.
                break;
            } else {
                array_pop($this->emailLines);                                   // Else, remove this line from the array
                // Interestingly enough, we don't need to do anything to $i this time...
            }
        }
    }
    
    /**
     * Check to see if this string starts with a letter
     *  
     *  Input:  The string to check
     */
    private function startsWithLetter($str) {
        return preg_match('/^[a-z]/i', $str);
    }
    
#pragma mark -----------------------------------------------------------------
#pragma mark Getters and Setters
    
    /**
     * Return a header value
     *  
     *  Input:  The header we want the value for
     */
    public function getHeader($header) {
        $h = strtolower($header);
        return (array_key_exists($h, $this->emailHeaders)) ? $this->emailHeaders[$h] : NULL;
    }

    /**
     * Return the entire array of headers.
     */
    public function getHeaders() {
        return $this->emailHeaders;
    }

    /**
     * Set the value of a particular header
     *  
     *  Input:  The header we want to set
     *          The value we want to set that header to
     */
    public function setHeader($header, $value) {
        $this->emailHeaders[strtolower($header)] = $value;
    }

    /**
     * Return the decoded message of the email
     *  
     *  Uses a best guess of possible email encodings
     */
    public function getMessage() {
        $text = '';
        switch ($this->getHeader('content-transfer-encoding')) {
            case 'quoted-printable':
                $text = quoted_printable_decode($this->emailMessage);
                break;
            case 'base64':
                $text = base64_decode($this->emailMessage);
                break;
            default:
                $text = $this->emailMessage;
        }
        return utf8_encode($text);
    }
    
    /**
     * Return the entire array of attachments
     */
    public function getAttachments() {
        return $this->emailAttachments;
    }
    
    /**
     * Dump all the particulars that the end user should want...
     *  
     *  Was more of a debugging thing, but can be useful for output
     */
    public function dump() {
        print_r(Array(
            $this->emailHeaders,
            $this->emailMessage,
            $this->emailAttachments
        ));
    }
}

$path = 'email.txt';
$file = file_get_contents($path);
$eP = new emailParser($file);
// $eP->dump();

echo "\n";
echo '---------------------------------------'."\n";
echo '---------------------------------------'."\n";
echo '---------------------------------------'."\n";
echo "\n";
echo "To: " . $eP->getHeader('to') . "\n";
echo "From: " . $eP->getHeader('from') . "\n";
echo "Subject: " . $eP->getHeader('subject') . "\n";
echo "Message:\n" . $eP->getMessage() . "\n";

echo "\n";
echo '---------------------------------------'."\n";
echo '---------------------------------------'."\n";
echo '---------------------------------------'."\n";
echo "\n";
echo "Attachments:\n";
foreach($eP->getAttachments() as $attachment) {
    print_r(Array(
        $attachment->getHeaders(),
        $attachment->getMessage()
    ));
}
?>