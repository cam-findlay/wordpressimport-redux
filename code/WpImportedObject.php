<?php

class WpImportedObject extends DataExtension
{
	function extraStatics($class = null, $extension = null) {
		return array(
			'db' => array(
				'WordpressID' => 'Int'
			)
		);
	}
}