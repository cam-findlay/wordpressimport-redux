<?php

class DeletPostsTask extends BuildTask
{
	protected $title = 'Delete blog posts task';

	protected $description = 'Deletes all blog posts and any associated comments.';

	public function init() {
		parent::init();

		if (!Permission::check('ADMIN'))
		{
			return Security::permissionFailure($this);
		}
	}

	public function run($request) {

		// Are there members with a clear text password?
		$posts = DataObject::get("BlogEntry");
		$count = 0;
		$commentCount = 0;
		foreach ($posts as $post)
		{
			// Delete comments 
			if (class_exists('Comments'))
			{
				$comments = $post->Comments();
				foreach ($comments as $comment)
				{
					$comment->Delete();
					$commentCount++;
				}
			}

			$count++;
			$post->deleteFromStage('Live');
			$post->delete();
		}

		if ($count)
			Debug::message("Deleted $count posts");
		else
			Debug::message("No posts deleted");

		if ($commentCount)
			Debug::message("Deleted $commentCount comments");
	}

}