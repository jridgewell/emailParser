<?php

/*
 * Email Parser Class.
 *
 * Handles plaintext emails.
 * Parses headers, message body and attachments.
 */

class emailParser {
	private $rawContent;
	private $emailLines;
	private $emailHeaders;
	private $emailMessage;
	private $emailAttachments;
	
	public function emailParser($raw) {
		$this->rawContent = $raw;
		$this->emailHeaders = Array();
		$this->emailAttachments = Array();
		$this->emailMessage = '';
		
		$this->normalizeLineEndings();
		$this->splitLines();
		$this->cleanArrayEdges();
		$this->parseEmailHeaders();
		$this->parseEmailMessage();
	}
	
#pragma mark -----------------------------------------------------------------
#pragma mark Parsing Functions

	private function parseEmailHeaders() {
		$started = false;
		$line = '';
		$matches = Array();
		$lastHeaderValue;
		while (sizeof($this->emailLines)) {
			$lastline = $line;
			$line = array_shift($this->emailLines);
			if ($started && $line == '') {
			//Beginning Message Body
				break;
			} else {
				$started = true;
				if ($this->startsWithLetter($line)) {
				//Begins New Header
					preg_match('/([^:]+):\s*(.*)$/', $line, $matches);
					$key = strtolower($matches[1]);
					if (!array_key_exists($key, $this->emailHeaders)) {
					//This is the first header of this type
						$this->emailHeaders[$key] = $matches[2];
						$lastHeaderValue = &$this->emailHeaders[$key];
					} else {
					//Sometimes, there are multiple Recieve headers, for instance.
						if (!is_array($this->emailHeaders[$key])) {
							$temp = Array($this->emailHeaders[$key]);
						} else {
							$temp = $this->emailHeaders[$key];
						}
						$i = array_push($temp, $matches[2]);
						$this->emailHeaders[$key] = $temp;
						$lastHeaderValue = &$this->emailHeaders[$key][$i - 1];
					}
				} else {
				//Part of Last Header
					$lastHeaderValue .= preg_replace('/^\s+/', ' ', $line);
				}
			}
		}
	}
	
	private function parseEmailMessage() {
		$contentType = $this->getHeader('content-type');
		if (strpos($contentType, 'boundary') !== false) {
			$this->parseMultiPartMessage();
		} else {
			$this->emailMessage = implode("\n", $this->emailLines);
			$this->emailLines = Array();
		}
	}
	
	private function parseMultiPartMessage() {
		$boundary = preg_replace('/[^=]+=(.+)/', "$1", $this->getHeader('content-type'));
		$boundary = preg_replace('/^"?([^"]+)"?$/', "$1", $boundary);
		$pattern = '/(--)?' . $boundary . '(--)?/';
		$messages = preg_split($pattern, $this->rawContent, NULL, PREG_SPLIT_NO_EMPTY);
		for ($i = 1; $i < sizeof($messages); ++$i) {
			$message = $messages[$i];
			$this->parseMessage($message);
		}
	}
	
	private function parseMessage($message) {
		$email = new emailParser($message);
		$contentType = $email->getHeader('content-type');
		$type = substr($contentType, 0, strpos($contentType, '/'));
		switch ($type) {
			case 'text':
				if (strpos($email->getHeader('content-disposition'), 'attachment') === false) {
					$this->emailMessage = $email->emailMessage;
					foreach ($email->getHeaders() as $key => $value) {
						$this->setHeader($key, $value);
					}
					break;
				}
			case 'image':
			case 'application':
				array_push($this->emailAttachments, $email);
		}
	}
	
#pragma mark -----------------------------------------------------------------
#pragma mark Helper Functions

	private function normalizeLineEndings() {
		$this->rawContent = preg_replace('/\r\n?/', "\n", $this->rawContent);
	}
	
	private function splitLines() {
		$this->emailLines = explode("\n", $this->rawContent);
	}

	private function cleanArrayEdges() {
		$line = '';
		// while (true) {
		for ($i = 0; $i < sizeof($this->emailLines); ++$i) {
			$line = $this->emailLines[$i];
			if (preg_match('/[a-z]/i', $line)) {
				break;
			}
			array_shift($this->emailLines);
			--$i;
		}
		for ($i = sizeof($this->emailLines) - 1; $i >= 0 ; --$i) {
			$line = $this->emailLines[$i];
			if (preg_match('/[a-z]/i', $line)) {
				break;
			}
			array_pop($this->emailLines);
		}
	}
	
	private function startsWithLetter($str) {
		return preg_match('/^[a-z]/i', $str);
	}
	
#pragma mark -----------------------------------------------------------------
#pragma mark Getters and Setters
	
	public function getHeader($header) {
		return (array_key_exists($header, $this->emailHeaders)) ? $this->emailHeaders[$header] : NULL;
	}

	public function getHeaders() {
		return $this->emailHeaders;
	}

	public function setHeader($header, $value) {
		$this->emailHeaders[strtolower($header)] = $value;
	}

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
	
	public function getAttachments() {
		return $this->emailAttachments;
	}
	
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