require "email_parser/version"

class EmailParser
  attr_reader :headers
  attr_reader :body
  attr_reader :attachments

  def initialize(raw)
    @raw = raw.lines.to_a
    @headers = {}
    @attachments = []
    
    parse_headers
    parse_body
    
    @raw = nil
  end

  def header(h)
    header = @headers[h.downcase.to_sym]
    header.join(', ') unless header.nil?
  end

  private

  ####################
  # Header Functions #
  ####################
  def parse_headers
    last_header = nil
    @raw.each_with_index do |unstripped_line, index|
      line = unstripped_line.strip
      if end_of_headers? line
        @raw.shift index + 1
        break
      elsif new_header? unstripped_line
        last_header = parse_header(line)
      else
        append_to_header last_header, line
      end
    end
  end
  
  def parse_header(line)
    header, value = parse_header_and_value(line)
    if header && value
      header = header.downcase.to_sym
      @headers[header] ||= []
      append_to_header header, value
      header
    end
  end
  

  ##################
  # Body Functions #
  ##################
  def parse_body
    content_type = header 'content-type'
    if content_type && content_type.match(/boundary=(.+)/)
      boundary = $1.gsub(/['"]/, '')
      parse_multipart_body boundary
    else
      parse_single_body
    end
  end
  
  def parse_single_body
    raw = @raw.join
    body = case header('content-transfer-encoding')
           when 'quoted-printable'
             raw.unpack 'M'
           when 'base64'
             raw.unpack 'm'
           else
             [raw]
           end
    @body = body.first.strip
  end
  
  def parse_multipart_body(boundary)
    @raw.shift(next_boundary_index(@raw, boundary) + 1)
    
    while (index = next_boundary_index(@raw, boundary))
      raw = @raw.shift(index)
      @raw.shift
      @attachments << EmailParser.new(raw.join)
    end
    
    @attachments.shift.tap do |main|
      @body = main.body
      @attachments = main.attachments.concat @attachments
      @headers = main.headers.merge @headers
    end
  end
  

  #####################
  # Utility Functions #
  #####################
  def blank_line?(line)
    line == ''
  end

  def end_of_headers?(line)
    blank_line? line
  end

  def new_header?(line)
    line =~ /^[^\s]/
  end

  def next_boundary_index(array, boundary)
    array.index { |value| value =~ /-*#{boundary}-*/ }
  end

  def parse_header_and_value(line)
    matches = line.match(/^([^:]+):\s*(.*)$/)
    if matches
      matches = matches.to_a
      matches.shift
      matches
    end
  end

  def append_to_header(header, value)
    @headers[header] << clean(value)
  end

  def clean(line)
    line.gsub(/,\s*$/, '')
  end
end
