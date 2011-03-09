<?php
/*
 * WpParser class 
 * Version		0.1
 * By			Saophalkun Ponlu @ Silverstripe
 *
 * This class is responsible for parsing Wordpress XML file into array of post entries. 
 * Post entry itself is an array containing entry data
 * Post entry (array):
 *		Title 			(mapped to SS blog entry)
 *		Link 
 *		Author 			(mapped to SS blog entry)
 *		Date 			(mapped to SS blog entry)
 *		UrlTitle 
 *		Tags 			(mapped to SS blog entry)
 *		Content 		(mapped to SS blog entry)
 *		Comments (array)
 *			Name 		(mapped to SS blog entry)
 *			Comment 	(mapped to SS blog entry)
 *			Created 	(mapped to SS blog entry)
 */
class WpParser {
	private $simple_xml;
	// xml namespaces
	private $namespaces;
	// array of post entries
	private $posts;
	
	public function __construct($filename) {
		$this->simple_xml = simplexml_load_file($filename) or die('Cannot open file.');
		$this->namespaces = $this->simple_xml->getNamespaces(TRUE);
		
	}
	
	/* 
	 * Posts getter
	 */
	public function getPosts() {
		return $this->posts;
		
	}
	
	/*
	 * Parses xml in $simple_xml to array of blog posts
	 * @return 		array of posts
	 */
	public function parse() {
		$sxml = $this->simple_xml;
		$namespaces = $this->namespaces;
		$posts = array();
		
		foreach ($sxml->channel->item as $item) {
			$post = array();
			// Get elements in namespaces
			$wp_ns = $item->children($namespaces['wp']);
			$content_ns = $item->children($namespaces['content']);
			$dc_ns = $item->children($namespaces['dc']);
			//Doesn't seem to like this namespace... remove it for now @TODO - work out wft is going on with that.
			//$wfw_ns = $item->children($namespaces['wfw']);

			$post['Title'] = (string) $item->title;
			$post['Link'] = (string) $item->link;
			$post['Author'] = (string) $dc_ns->creator;
			
			// Uses this array to check if the category to be added already exists
			// in the post
			$categories = array();
			// Stores all categories and tags of the post in a string var
			$tags = '';
			
			
			foreach ($item->category as $cat) {
			/**
			 * is this in tags or categories? We only want categories to become SS Tags
			 */
			if($cat['domain'] == "category"){

				if (!in_array($cat, $categories)) {
					$categories[] = (string)$cat;
					$tags .= $cat.', ';
				}	
				
				}//end in category
				
			}
			
				
			
			$tags = substr($tags, 0, strlen($tags)-2);
			
			$post['Tags'] = $tags;
			
			
			
			/**
			 * change the wp-content link to assets allowing you to migrate images from WP to SS assets folder.
			 */
			$migrated_content = str_replace('/wp-content/uploads/', '/assets/Uploads', (string)$content_ns->encoded);
			
			
			$post['Content'] = $migrated_content;
			
			
			
			
			$post['UrlTitle'] = (string) $wp_ns->post_name;
			$post['Date'] = (string) $wp_ns->post_date;
			
			// Array of comments of a post 
			$comments = array();
			foreach ($wp_ns->comment as $c) {
				// each comment
				$comment = array();
				// $c is not an array but SimpleXML object
				$comment['Name'] = (string)$c->comment_author;
				$comment['Comment'] = (string)$c->comment_content;
				$comment['Created'] = (string)$c->comment_date;
				$comments[] = $comment;
			}
			$post['Comments'] = $comments;
			$posts[] = $post;
		}
		// Also stores posts array in the class 
		// before returning 
		$this->posts = $posts;
		return $this->posts;
	}
}
?>