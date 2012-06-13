<?php

class emailParser {
	private $emailContent;
	private $emailLines;
	private $emailHeaders;
	
	public function emailParser($content) {
		$this->emailHeaders = Array();

		$this->emailContent = $content;
		$this->normalizeLineEndings();
		$this->splitLines();
		$this->parseEmailHeaders();
	}
	
	private function normalizeLineEndings() {
		$this->emailContent = preg_replace('/\r\n?/', "\n", $this->emailContent);
	}
	
	private function splitLines() {
		$this->emailLines = explode("\n", $this->emailContent);
	}
	
	private function parseEmailHeaders() {
		$line = '';
		while ($line = array_shift($this->emailLines)) {
			if ($line == '') {
			//Beginning Message Body
				break;
			}
			//Begins New Header
			if ($this->startsWithLetter($line)) {
				echo 'yes' . "\n";
			} else {
			//Part of Last Header
				echo 'last' . "\n";
			}
		}
	}
	
	private function startsWithLetter($str) {
		return preg_match('/^[a-zA-Z]/', $str);
	}
	
	public function dump() {
		print_r($this);
	}
}

$path = 'email.txt';
$file = file_get_contents($path);
$eP = new emailParser($file);
$eP->dump();

?>