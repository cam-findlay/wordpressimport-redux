<?php

/**
 * @package comments
 */
class WpParserTests extends FunctionalTest
{
	static $testImportFile = 'WordpressExport.xml';

	protected function buildTestParser() {
		$path = dirname(__FILE__) . '/' . self::$testImportFile;
		return new WpParser($path);
	}

	public function testRewriteImageURLs() {
		$parser = $this->buildTestParser();

		// Test parsing urls with hostname
		$imageIn = '<p>Here is an image <img src="http://localhost/wp-content/uploads/2012/11/image.jpg" /> that I uploaded</p>';
		$imageOutExpected = '<p>Here is an image <img src="/assets/Uploads/2012/11/image.jpg" /> that I uploaded</p>';
		$imageOut = $parser->ParseBlogContent($imageIn);
		$this->assertEquals($imageOutExpected, $imageOut);

		// Test parsing urls without hostname
		$imageIn = '<p>Here is an image <img src="/wp-content/uploads/2012/11/image.jpg" /> that I uploaded</p>';
		$imageOutExpected = '<p>Here is an image <img src="/assets/Uploads/2012/11/image.jpg" /> that I uploaded</p>';
		$imageOut = $parser->ParseBlogContent($imageIn);
		$this->assertEquals($imageOutExpected, $imageOut);
	}

	public function testBuildParagraphs() {
		$parser = $this->buildTestParser();
		
		$expected = "<p>Here is a test paragraph</p><p><img src=\"/assets/Uploads/2012/08/test-image-300x219.jpg\" alt=\"Test Image\" /></p><p>Another paragraph</p>";
		$input = "Here is a test paragraph\t\n\r\n\t\t\t\t<img src=\"/assets/Uploads/2012/08/test-image-300x219.jpg\" alt=\"Test Image\" />\n\n\t\t\t\t\n\n\t\t\t\tAnother paragraph";
		$output = $parser->ParseBlogContent($input);
		
		$this->assertEquals($expected, $output, "Failed parsing paragraphs. Expected \"" . addcslashes($expected, "\r\n\t") . "\", returned \"" . addcslashes($output, "\r\n\t") . "\"");
	}

	public function testCanParsePosts() {
		$parser = $this->buildTestParser();
		$posts = $parser->parse();

		// Have we got a post? The page (non post) should not be parsed
		$this->assertEquals(1, count($posts), 'Assert single post parsed');
		if (empty($posts))
			return;

		// Did it parse correctly?
		$firstPost = $posts[0];
		$expectedPost = array(
			'Title' => 'Test Post',
			'Link' => 'http://localhost/2011/08/test-post/',
			'Author' => 'Test User',
			'Tags' => 'bar, buzz',
			'Content' => "<p>Here is a test paragraph</p><p><img src=\"/assets/Uploads/2012/08/test-image-300x219.jpg\" alt=\"Test Image\" /></p><p>Another paragraph</p>",
			'URLSegment' => 'test-post',
			'Date' => '2011-08-18 10:52:37',
			'WordpressID' => 79,
			'ProvideComments' => true,
			'IsPublished' => true
		);
		foreach ($expectedPost as $key => $value)
		{
			$actual = trim($firstPost[$key]);
			$expected = trim($value);
			$this->assertEquals($expected, $actual, "Parsing field $key expected \"" . addcslashes($expected, "\r\n\t") . "\", returned \"" . addcslashes($actual, "\r\n\t") . "\"");
		}
	}

	public function testCanParseComments() {
		// Attempt setup
		$parser = $this->buildTestParser();
		$posts = $parser->parse();
		if (empty($posts))
			$this->fail('Could not setup test testCanParseComments');
		$firstPost = $posts[0];

		// Have we got a comment?
		$comments = $firstPost['Comments'];
		$this->assertEquals(1, count($comments), 'Assert single comment parsed');
		if (empty($comments))
			return;

		// Did it parse correctly?
		$firstComment = $comments[0];
		$expectedComment = array(
			'Name' => 'Test Comment Person',
			'Email' => 'commenter@yahoo.com',
			'URL' => 'http://answers.yahoo.com/',
			'Comment' => 'This is a great test',
			'Created' => '2011-09-01 03:08:00',
			'Moderated' => true,
			'WordpressID' => 9
		);
		foreach ($expectedComment as $key => $value)
		{
			$actual = trim($firstComment[$key]);
			$expected = trim($value);
			$this->assertEquals($expected, $actual, "Parsing field $key expected \"" . addcslashes($expected, "\r\n\t") . "\", returned \"" . addcslashes($actual, "\r\n\t") . "\"");
		}
	}

}