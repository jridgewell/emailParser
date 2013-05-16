emailParser
===========

A Coding Practical before a job interview.


PHP
---

I avoided using PHP's IMAP functions, it seemed like cheating. This will parse the headers into an array, the message body, and stores any attachments in a array. There is basic usage in the bottom of the implementation.

### What else is there to do?

* Write unit tests
* Implement a way to search for an attachment
* More encodings


Ruby Gem
--------

More or less the same as the PHP implementation. This will parse the headers into a hash, the message body, and stores any attachments in a array. There is basic usage in the spec files.

### What else is there to do?

* Separate into individual modules
* Comment code
* Implement a way to search for an attachment
* More encodings