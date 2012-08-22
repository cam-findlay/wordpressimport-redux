<?php
// Add wordpress import extension to BlogHolder page type
if (class_exists('BlogHolder'))
{
	Object::add_extension('BlogHolder', 'WpImporter');
	Object::add_extension('BlogEntry', 'WpImportedObject');
}

if(class_exists('Comment'))
	Object::add_extension('Comment', 'WpImportedObject');