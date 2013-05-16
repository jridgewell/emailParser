require 'spec_helper'

describe EmailParser do
  describe 'handles' do
    expected_body = 'This is a message'
    describe 'single message' do
      it 'without content-type' do
        raw = "From: justin@ridgewell.name\n" <<
          "\n" <<
          "This is a message"
        e = EmailParser.new raw
        e.body.should == expected_body
      end

      it 'with content-type' do
        raw = "From: justin@ridgewell.name\n" <<
          "Content-Type: text/plain; charset=us-ascii\n" <<
          "\n" <<
          "This is a message"
        e = EmailParser.new raw
        e.body.should == expected_body
      end
    end

    describe 'multipart messages' do
      it 'without headers' do
        raw = "Content-Type: multipart/alternative; boundary=\"089e0111c130bfea4c04dc367352\"\n" <<
          "\n" <<
          "--089e0111c130bfea4c04dc367352\n" <<
          "\n" <<
          "This is a message\n" <<
          "\n" <<
          "--089e0111c130bfea4c04dc367352\n" <<
          "\n" <<
          "This is the second message\n" <<
          "\n" <<
          "--089e0111c130bfea4c04dc367352--\n"
        e = EmailParser.new raw
        e.body.should == expected_body
        e.attachments.size.should == 1
        e.attachments[0].body.should == "This is the second message"
      end

      it 'with headers' do
        raw = "Content-Type: multipart/alternative; boundary=\"089e0111c130bfea4c04dc367352\"\n" <<
          "\n" <<
          "--089e0111c130bfea4c04dc367352\n" <<
          "Content-Transfer-Encoding: base64\n" <<
          "\n" <<
          ["This is a message\n"].pack('m') <<
          "\n" <<
          "\n" <<
          "--089e0111c130bfea4c04dc367352\n" <<
          "Content-Transfer-Encoding: quoted-printable\n" <<
          "\n" <<
          ["This is the second message"].pack('M') <<
          "\n" <<
          "\n" <<
          "--089e0111c130bfea4c04dc367352--\n"
        e = EmailParser.new raw
        e.body.should == expected_body
        e.attachments[0].body.should == "This is the second message"
      end
    end
  end
end
