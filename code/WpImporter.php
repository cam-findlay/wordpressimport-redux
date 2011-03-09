<?php
require('WpParser.php');

/* 
 * Decorates a BlogHolder page type, specified in _config.php
 */ 
class WpImporter extends DataObjectDecorator {

	function updateCMSFields(&$fields) {
			$html_str = '<iframe name="WpImport" src="WpImporter_Controller/index/'.$this->owner->ID.'" width="500"> </iframe>';
			$fields->addFieldToTab('Root.Content.Import', new LiteralField("ImportIframe",$html_str));		
	}
}

class WpImporter_Controller extends Controller {
	// Do security check in case this controller is called by unauthorised user using direct url
	function init() {
		parent::init();
		if(!Permission::check("ADMIN")) Security::permissionFailure();
	}
	
	/*
	 * Required
	 */
	function Link() {
		return $this->class.'/';	
	}

	/*
	 * Outputs an file upload form
	 */
	function UploadForm() {
		return new Form($this, "UploadForm", new FieldSet(
			new FileField("XMLFile", 'Wordpress XML file'),
			new HiddenField("BlogHolderID", '', $this->urlParams['ID'])
		), new FieldSet(
			new FormAction('doUpload', 'Import Wordpress XML file')
		));
	}
	
	function doUpload($data, $form) {
		// Gets a blog holders ID 
		$blogHolderID = $data['BlogHolderID'];
		
		// Checks if a file is uploaded
		if(is_uploaded_file($_FILES['XMLFile']['tmp_name'])) {
			echo '<p>Processing...<br/></p>';
			flush();
			$file = $_FILES['XMLFile'];
			// check file type. only xml file is allowed
			if ($file['type'] != 'text/xml') {
				echo 'Please select Wordpress XML file';
				die;
			}
			
			$wp = new WpParser($file['tmp_name']);
			$posts = $wp->parse();
			
			// For testing only
			// TODO: remove $count
			//$count = 0;
			foreach ($posts as $post) {
				$comments = $post['Comments'];
				// create a blog entry
				$entry = new BlogEntry();
				$entry->ParentID = $blogHolderID;
				// $posts array and $entry have the same key/field names
				// so we can use update here.
				
				$entry->update($post);
				$entry->write();
				$entry->publish("Stage", "Live");
				
				// page comment(s)
				foreach ($comments as $comment) {
					$page_comment = new PageComment();
					$page_comment->ParentID = $entry->ID;
					$page_comment->update($comment);
					$page_comment->write();
				}
				// count is used for testing only
				// TODO: remove the next 2 lines
				//$count++;
				//if($count==30) break;
			}
			
			// delete the temporaray uploaded file
			unlink($file['tmp_name']);
			// print sucess message
			echo 'Complete!<br/>';
			echo 'Please refresh the admin page to see the new blog entries.';
		}
		
		
	}

}
?>