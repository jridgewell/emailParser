require 'spec_helper'

describe EmailParser do
  describe 'handles' do
    expected_body = 'This is ASCII text'
    it 'messages with no encoding' do
      raw = "\n" <<
        "This is ASCII text"
      e = EmailParser.new raw
      e.body.should == expected_body
    end

    it 'messages with quoted-printable encoding' do
      raw = "Content-Transfer-Encoding: quoted-printable\n" <<
        "\n" <<
        ["This is ASCII text"].pack('M')
      e = EmailParser.new raw
      e.body.should == expected_body
    end

    it 'messages with base64 encoding' do
      raw = "Content-Transfer-Encoding: base64\n" <<
        "\n" <<
        ["This is ASCII text"].pack('m')
      e = EmailParser.new raw
      e.body.should == expected_body
    end
  end
end
