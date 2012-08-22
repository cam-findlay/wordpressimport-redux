<?php
require('WpParser.php');

/*
 * Decorates a BlogHolder page type, specified in _config.php
 */

class WpImporter extends DataExtension
{

	function updateCMSFields(FieldList $fields) {
		$html_str = '<iframe name="WpImport" src="WpImporter_Controller/index/' . $this->owner->ID . '" width="500"> </iframe>';
		$fields->addFieldToTab('Root.Import', new LiteralField("ImportIframe", $html_str));
	}

}

class WpImporter_Controller extends Controller
{

	function init() {
		parent::init();

		// Do security check in case this controller is called by unauthorised user using direct url
		if (!Permission::check("ADMIN"))
			Security::permissionFailure();

		// Check for requirements
		if (!class_exists('BlogHolder'))
			user_error('Please install the blog module before importing from Wordpress', E_USER_ERROR);
	}

	protected function getBlogHolderID() {
		if (isset($_REQUEST['BlogHolderID']))
			return $_REQUEST['BlogHolderID'];

		return $this->request->param('ID');
	}

	/*
	 * Outputs an file upload form
	 */

	function UploadForm() {
		return new Form($this, "UploadForm",
						new FieldList(
								new FileField("XMLFile", 'Wordpress XML file'),
								new HiddenField("BlogHolderID", '', $this->getBlogHolderID())
						),
						new FieldList(
								new FormAction('doUpload', 'Import Wordpress XML file')
						)
		);
	}

	protected function getOrCreateComment($wordpressID) {
		if ($wordpressID && $comment = DataObject::get('Comment')->filter(array('WordpressID' => $wordpressID))->first())
			return $comment;

		return Comment::create();
	}

	protected function importComments($post, $entry) {
		if (!class_exists('Comment'))
			return;

		$comments = $post['Comments'];
		foreach ($comments as $comment)
		{
			$page_comment = $this->getOrCreateComment($comment['WordpressID']);
			$page_comment->update($comment);
			$page_comment->ParentID = $entry->ID;
			$page_comment->write();
		}
	}

	protected function getOrCreatePost($wordpressID) {
		if ($wordpressID && $post = DataObject::get('BlogEntry')->filter(array('WordpressID' => $wordpressID))->first())
			return $post;

		return BlogEntry::create();
	}

	protected function importPost($post) {
		// create a blog entry
		$entry = $this->getOrCreatePost($post['WordpressID']);

		$entry->ParentID = $this->getBlogHolderID();

		// $posts array and $entry have the same key/field names
		// so we can use update here.

		$entry->update($post);
		$entry->write();
		if ($post['IsPublished'])
			$entry->publish("Stage", "Live");

		$this->importComments($post, $entry);

		return $entry;
	}

	function doUpload($data, $form) {

		// Checks if a file is uploaded
		if (!is_uploaded_file($_FILES['XMLFile']['tmp_name']))
			return;

		echo '<p>Processing...<br/></p>';
		flush();
		$file = $_FILES['XMLFile'];
		// check file type. only xml file is allowed
		if ($file['type'] != 'text/xml')
		{
			echo 'Please select Wordpress XML file';
			die;
		}

		// Parse posts
		$wp = new WpParser($file['tmp_name']);
		$posts = $wp->parse();
		foreach ($posts as $post)
			$this->importPost($post);

		// delete the temporaray uploaded file
		unlink($file['tmp_name']);

		// print sucess message
		echo 'Complete!<br/>';
		echo 'Please refresh the admin page to see the new blog entries.';
	}

}