<?php

class emailParser {
	private $rawContent;
	private $emailLines;
	private $emailHeaders;
	private $emailMessage;
	
	public function emailParser($raw) {
		$this->rawContent = $raw;
		$this->emailHeaders = Array();
		$this->emailHeaders = Array();
		$this->emailMessage = '';
		
		$this->normalizeLineEndings();
		$this->splitLines();
		$this->parseEmailHeaders();
		$this->parseEmailMessage();
	}
	
	private function normalizeLineEndings() {
		$this->rawContent = preg_replace('/\r\n?/', "\n", $this->rawContent);
	}
	
	private function splitLines() {
		$this->emailLines = explode("\n", $this->rawContent);
	}
	
	private function parseEmailHeaders() {
		$line = '';
		$matches = Array();
		$lastHeaderValue;
		while ($line = array_shift($this->emailLines)) {
			if ($line == '') {
			//Beginning Message Body
				break;
			}
			//Begins New Header
			if ($this->startsWithLetter($line)) {
				preg_match('/([^:]+):\s*(.*)$/', $line, $matches);
				$key = strtolower($matches[1]);
				if (!array_key_exists($key, $this->emailHeaders)) {
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
	
	private function parseEmailMessage() {
		$this->emailMessage = implode("\n", $this->emailLines);
		$this->emailLines = Array();
	}
	
	private function startsWithLetter($str) {
		return preg_match('/^[a-zA-Z]/', $str);
	}
	
	public function getHeader($header) {
		return (array_key_exists($header, $this->emailHeaders)) ? $this->emailHeaders[$header] : NULL;
	}

	public function getMessage() {
		return $this->emailMessage;
	}
	
	public function dump() {
		print_r($this);
	}
}

$path = 'email.txt';
$file = file_get_contents($path);
$eP = new emailParser($file);
echo "To: " . $eP->getHeader('to') . "\n";
echo "From: " . $eP->getHeader('from') . "\n";
echo "Subject: " . $eP->getHeader('subject') . "\n";
echo "Message:\n" . $eP->getMessage() . "\n";

?>