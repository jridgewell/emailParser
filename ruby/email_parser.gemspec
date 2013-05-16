# coding: utf-8
lib = File.expand_path('../lib', __FILE__)
$LOAD_PATH.unshift(lib) unless $LOAD_PATH.include?(lib)
require 'email_parser/version'

Gem::Specification.new do |spec|
  spec.name          = 'EmailParser'
  spec.version       = EmailParser::VERSION
  spec.authors       = ['Justin Ridgewell']
  spec.email         = ['justin@ridgewell.name']
  spec.summary       = %q{An email parser written for a job interview}
  spec.license       = 'MIT'

  spec.files         = `git ls-files`.split($/)
  spec.executables   = spec.files.grep(%r{^bin/}) { |f| File.basename(f) }
  spec.test_files    = spec.files.grep(%r{^(test|spec|features)/})
  spec.require_paths = ['lib']

  spec.add_development_dependency 'bundler', '~> 1.3'
  spec.add_development_dependency 'pry', '~> 0.9'
  spec.add_development_dependency 'pry-debugger', '~> 0.2'
  spec.add_development_dependency 'rspec', '~> 2.13'
end
