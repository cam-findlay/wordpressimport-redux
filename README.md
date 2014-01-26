#Wordpress Import Module
[![Build Status](https://travis-ci.org/camfindlay/silverstripe-wordpressimport.png?branch=master)](https://travis-ci.org/camfindlay/silverstripe-wordpressimport)

##Maintainer Contacts
* Cam Findlay (Nickname: camfindlay) <cam (at) silverstripe.com>
* Damian Mooyman (Nickname: tractorcow) <damian (dot) mooyman (at) gmail (dot) com>


##Requirements
* mod_rewrite (optional, if you need to cater for old incoming links and are using Apache)
* SilverStripe Framework & CMS 3.1.x
* silverstripe/blog
* silverstripe/comments

##Installation Instructions

    composer require silverstripe/wordpressimport 0.3.*

###Usage Overview
It will change any links to uploaded images and 
files in your posts that follow the convention 
"http://yourdomain.com/wp-content/uploads/yyyy/mm/filesname.jpg" 
to "http://yourdomain.com/assets/Uploads/yyyy/mm/filesname.jpg" 
which allows you to migrate you uploaded images 
and files over to SilverStripe assets folder while maintaining 
images in your posts.

###Optional Rewriting
Add this in your .htaccess file to port old 
wordpress posts in the form /yyyy/mm/name-of-post/
 to new SilverStripe /blog/name-of-post convention.


    RewriteRule ^[0-9]{4}/[0-9]{2}/(.*)$ /blog/$1 [R,L]


##Known issues:
1. Content can lose a lot of the formatting coming from Wordpress.
1. Perhaps parsing the content through a nl2br might help?
1. Image captions need to be catered for and styled otherwise they end up looking like un-parse shortcodes.


##License
Copyright (c) 2008, Silverstripe Ltd.
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
     * Redistributions of source code must retain the above copyright
       notice, this list of conditions and the following disclaimer.
     * Redistributions in binary form must reproduce the above copyright
       notice, this list of conditions and the following disclaimer in the
       documentation and/or other materials provided with the distribution.
     * Neither the name of the Silverstripe Ltd. nor the
       names of its contributors may be used to endorse or promote products
       derived from this software without specific prior written permission.

 THIS SOFTWARE IS PROVIDED BY Silverstripe Ltd. ``AS IS'' AND ANY
 EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 DISCLAIMED. IN NO EVENT SHALL Silverstripe Ltd. BE LIABLE FOR ANY
 DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.




