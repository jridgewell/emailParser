require 'spec_helper'

describe EmailParser do
  describe 'handles' do
    it 'a header' do
      raw = "To: <justin@ridgewell.name>"
      e = EmailParser.new raw
      e.headers.size.should == 1
      e.header(:to).should == '<justin@ridgewell.name>'
    end

    it 'multiple headers' do
      raw = "To: <justin@ridgewell.name>\r\n" <<
        "From: <test@test.com>\r\n" <<
        "Subject: Testing!"
      e = EmailParser.new raw
      e.headers.size.should == 3
    end

    it 'continuation of a header' do
      raw = "To: <justin@ridgewell.name>,\r\n" <<
        "  <test@test.com>"
      e = EmailParser.new raw
      e.headers.size.should == 1
      e.header(:to).should == '<justin@ridgewell.name>, <test@test.com>'
    end

    it 'multiple of the same header' do
      raw = "To: <justin@ridgewell.name>\r\n" <<
        "To: <test@test.com>"
      e = EmailParser.new raw
      e.headers.size.should == 1
      e.header(:to).should == '<justin@ridgewell.name>, <test@test.com>'
    end
  end
end
