/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	var baseurl = 'http://localhost/impress/js';
	config.filebrowserBrowseUrl = baseurl+'/kcfinder/browse.php?type=files';
	config.filebrowserImageBrowseUrl = baseurl+'/kcfinder/browse.php?type=images';
	config.filebrowserFlashBrowseUrl = baseurl+'/kcfinder/browse.php?type=flash';
	config.filebrowserUploadUrl = baseurl+'/kcfinder/upload.php?type=files';
	config.filebrowserImageUploadUrl = baseurl+'/kcfinder/upload.php?type=images';
	config.filebrowserFlashUploadUrl = baseurl+'/kcfinder/upload.php?type=flash';

};
