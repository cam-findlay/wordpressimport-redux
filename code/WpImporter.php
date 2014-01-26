<?php
require('WpParser.php');

/*
 * Decorates a BlogHolder page type, specified in _config.php
 */

class WpImporter extends DataExtension
{

	public function updateCMSFields(FieldList $fields) {
		$html_str = '<iframe name="WpImport" src="WpImporter_Controller/index/' . $this->owner->ID . '" width="500"> </iframe>';
		$fields->addFieldToTab('Root.Import', new LiteralField("ImportIframe", $html_str));
	}

}

class WpImporter_Controller extends Controller
{
	private static $allowed_actions = array(
		'index',
		'UploadForm',
		'doUpload'
	);

	public function init() {
		parent::init();

		// Do security check in case this controller is called by unauthorised user using direct url
		if (!Permission::check("ADMIN"))
			Security::permissionFailure();

		// Check for requirements
		if (!class_exists('BlogHolder'))
			user_error('Please install the blog module before importing from Wordpress', E_USER_ERROR);
	}

	public function index($request) {
		return $this->renderWith('WpImporter');
	}

	protected function getBlogHolderID() {
		if (isset($_REQUEST['BlogHolderID']))
			return $_REQUEST['BlogHolderID'];

		return $this->request->param('ID');
	}

	/*
	 * Outputs an file upload form
	 */

	public function UploadForm() {
		return Form::create($this, "UploadForm",
						FieldList::create(
							FileField::create("XMLFile", 'Wordpress XML file'),
							HiddenField::create("BlogHolderID", '', $this->getBlogHolderID())
						),
						FieldList::create(
							FormAction::create('doUpload', 'Import Wordpress XML file')
						)
		);
	}

	protected function getOrCreateComment($wordpressID) {
		if ($wordpressID && $comment = Comment::get()->filter(array('WordpressID' => $wordpressID))->first())
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
		if ($wordpressID && $post = BlogEntry::get()->filter(array('WordpressID' => $wordpressID))->first())
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

		//Create an initial write as a draft copy otherwise a write() 
		//in SS3.1.2+ will go live and never have a draft Version.
		//@see http://doc.silverstripe.org/framework/en/changelogs/3.1.2#default-current-versioned-
		//stage-to-live-rather-than-stage for details.
		$entry->writeToStage('Stage');
		
		//If the post was published on WP, now ensure it is also live in SS.
		if ($post['IsPublished']){
			$entry->publish("Stage", "Live");
		}

		$this->importComments($post, $entry);

		return $entry;
	}

	public function doUpload($data, $form) {

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