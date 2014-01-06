<?php
/*
 * WpParser class 
 * Version		0.1
 * By			Saophalkun Ponlu @ Silverstripe
 *
 * This class is responsible for parsing Wordpress XML file into array of post entries. 
 * Post entry itself is an array containing entry data
 * Post entry (array):
 * 		Title 			(mapped to SS blog entry)
 * 		Link 
 * 		Author 			(mapped to SS blog entry)
 * 		Date 			(mapped to SS blog entry)
 * 		UrlTitle 
 * 		Tags 			(mapped to SS blog entry)
 * 		Content 		(mapped to SS blog entry)
 * 		Comments (array)
 * 			Name 		(mapped to SS blog entry)
 * 			Comment 	(mapped to SS blog entry)
 * 			Created 	(mapped to SS blog entry)
 */

class WpParser
{
	private $simple_xml;

	// xml namespaces
	private $namespaces;

	// array of post entries
	private $posts;

	/**
	 * List of "tag" types that should be converted to tags
	 * @var array List of valid tags
	 */
	public static $allowed_category_domains = array('category', 'post_tag');

	/**
	 * List of "page" types that should be converted to BlogEntry items
	 * @param array List of valid types
	 */
	public static $allowed_page_types = array('post');

	public function __construct($filename) {
		$this->simple_xml = simplexml_load_file($filename) or die('Cannot open file.');
		$this->namespaces = $this->simple_xml->getNamespaces(TRUE);
	}

	/*
	 * Retrieves all parsed posts
	 */

	public function getPosts() {
		return $this->posts;
	}

	/**
	 * Extracts the categories from the blog post in the form of a single tag
	 * value suitable for BlogPost
	 * @param array $cats list of categories
	 * @return string A string of comma separated tag values
	 */
	public function ParseTags($cats) {
		// Uses this array to check if the category to be added already exists in the post
		$categories = array();
		foreach ($cats as $cat)
		{			
			// Cleanup multiline and other whitespace characters
			$catName = html_entity_decode(trim(preg_replace('/\s+/m', ' ', (string)$cat)));
			
			// is this in tags or categories? We only want categories to become SS Tags
			if (in_array($cat['domain'], self::$allowed_category_domains) && !in_array($catName, $categories))
				$categories[] = (string) $catName;
		}
		return join(', ', $categories);
	}

	/**
	 * Parses and cleans up the body of the wordpress blog post
	 * @param mixed $content_ns The XML object containing the wordpress post body
	 * @return string The parsed content block in HTML format
	 */
	public function ParseBlogContent($content) {

		// Convert wordpress-style image links to silverstripe asset filepaths
		$content = preg_replace('/(http:\/\/[\w\.\/]+)?\/wp-content\/uploads\//i', '/assets/Uploads/', $content);

		// Split multi-line blocks into paragraphs
		$split = preg_split('/\s*\n\s*\n\s*/im', $content);
		$content = '';
		foreach ($split as $paragraph)
		{
			$paragraph = trim($paragraph);
			if (empty($paragraph))
				continue;
			
			if(preg_match('/^<p>.*/i', $paragraph))
				$content .= $paragraph;
			else
				$content .= "<p>$paragraph</p>";
		}
		
		// Split single-line blocks with line-breaks
		$content = nl2br($content);

		return $content;
	}

	/**
	 * Parses a single comment
	 * @param mixed $comment The XML object containing the comment data
	 * @return array The comment object encoded as an array
	 */
	protected function parseComment($comment) {
		return array(
			'Name' => (string) $comment->comment_author,
			'Email' => (string) $comment->comment_author_email,
			'URL' => (string) $comment->comment_author_url,
			'Comment' => (string) $comment->comment_content,
			'Created' => (string) $comment->comment_date,
			'Moderated' => !!$comment->comment_approved,
			'WordpressID' => intval($comment->comment_id)
		);
	}

	/**
	 * Extracts comments from the wordpress post
	 * @param mixed $wp_ns The XML entity containing comments
	 * @return array List of comments, each formatted as an array
	 */
	protected function parseComments($wp_ns) {

		// Array of comments of a post 
		$comments = array();
		foreach ($wp_ns->comment as $comment)
			$comments[] = $this->parseComment($comment);
		return $comments;
	}

	/**
	 * Parses a single blog post
	 * @param mixed $item The XML object containing the blog post
	 * @param mixed $namespaces The XML object containing namespace identifiers
	 * @return array The blog post encoded as an array
	 */
	protected function parsePost($item, $namespaces) {
		// Get elements in namespaces
		$wp_ns = $item->children($namespaces['wp']);
		$content_ns = $item->children($namespaces['content']);
		$dc_ns = $item->children($namespaces['dc']);

		// Filter out non-post types (attachments, pages, etc)
		if (!in_array($wp_ns->post_type, self::$allowed_page_types))
			return null;

		return array(
			'Title' => (string) $item->title,
			'Link' => (string) $item->link,
			'Author' => (string) $dc_ns->creator,
			'Tags' => $this->ParseTags($item->category),
			'Content' => $this->ParseBlogContent((string) $content_ns->encoded),
			'URLSegment' => (string) $wp_ns->post_name,
			'Date' => (string) $wp_ns->post_date,
			'Comments' => $this->parseComments($wp_ns),
			'WordpressID' => intval($wp_ns->post_id),
			'ProvideComments' => ($wp_ns->comment_status == 'open'),
			'IsPublished' => ($wp_ns->status == 'publish') // Used later to trigger ->publish in the importer
		);
	}

	/*
	 * Parses xml in $simple_xml to array of blog posts
	 * @return array of posts
	 */

	public function parse() {
		$namespaces = $this->namespaces;

		$posts = array();
		foreach ($this->simple_xml->channel->item as $item)
		{
			// Import this post if a valid item is returned
			if ($post = $this->parsePost($item, $namespaces))
				$posts[] = $post;
		}
		return $this->posts = $posts;
	}

}