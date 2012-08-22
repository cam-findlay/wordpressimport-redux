<?php

/* Add wordpress import extension to BlogHolder page type */
if(class_exists('BlogHolder'))
	Object::add_extension('BlogHolder', 'WpImporter');