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
  def parse_headers
    last_header = nil
    @raw.each_with_index do |unstripped_line, index|
      line = unstripped_line.strip
      if is_blank_line? line
        @raw.shift index + 1
        break
      elsif is_new_header? unstripped_line
        header, value = extract_header_value(line)
        header = header.downcase.to_sym
        last_header = header
        @headers[header] ||= []
        @headers[header] << clean(value)
      else
        @headers[last_header] << clean(line)
      end
    end
  end

  def parse_body
    content_type = header 'content-type'
    if content_type && content_type.match(/boundary=(.+)/)
      boundary = $1.gsub(/['"]/, '')
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
    else
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
  end

  def is_blank_line?(line)
    line == ''
  end

  def is_new_header?(line)
    line =~ /^[^\s]/
  end

  def next_boundary_index(array, boundary)
    array.index { |value| value =~ /-*#{boundary}-*/ }
  end

  def extract_header_value(line)
    matches = line.match(/^([^:]+):\s*(.*)$/)
    if matches
      matches = matches.to_a
      matches.shift
      matches
    end
  end

  def clean(line)
    line.gsub(/,\s*/, '')
  end
end
