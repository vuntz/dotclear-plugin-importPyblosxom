<?php
# ***** BEGIN LICENSE BLOCK *****
#
# Author: Vincent Untz <vincent@vuntz.net>
# Copyright (C) 2005 Vincent Untz
#
# Version: MPL 1.1/GPL 2.0/LGPL 2.1
#
# The contents of this file are subject to the Mozilla Public License Version
# 1.1 (the "License"); you may not use this file except in compliance with
# the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS" basis,
# WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
# for the specific language governing rights and limitations under the
# License.
#
# Some of the code comes from the DotClear Weblog.
# The Initial Developer of the Original Code is
# Olivier Meunier.
# Portions created by the Initial Developer are Copyright (C) 2003
# the Initial Developer. All Rights Reserved.
#
# Contributor(s):
#
# Alternatively, the contents of this file may be used under the terms of
# either the GNU General Public License Version 2 or later (the "GPL"), or
# the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
# in which case the provisions of the GPL or the LGPL are applicable instead
# of those above. If you wish to allow use of your version of this file only
# under the terms of either the GPL or the LGPL, and not to allow others to
# use your version of this file under the terms of the MPL, indicate your
# decision by deleting the provisions above and replace them with the notice
# and other provisions required by the GPL or the LGPL. If you do not delete
# the provisions above, a recipient may use your version of this file under
# the terms of any one of the MPL, the GPL or the LGPL.
#
# ***** END LICENSE BLOCK *****

function pyblosxom_getCommentElement ($root, $tagname, $comment_file)
{
	global $warn;

	$node_array = $root->get_elements_by_tagname($tagname);

	if (count($node_array) > 1) {
		$warn .= '<li>'.sprintf(__('More than one %s element in comment "%s".'), $tagname, $comment_file).'</li>';
	}

	if (isset($node_array[0])) {
		return $node_array[0]->get_content();
	} else {
		return NULL;
	}
}

function pyblosxom_createComment ($post_id = 0, $comment_file = "", $tidy = false)
{
	global $err;
	global $warn;
	global $blog;

	if (file_exists($comment_file) == false) {
		$warn .= '<li>'.sprintf(__('Comment file "%s" does not exist.'), $comment_file).'</li>';
		return;
	}
	if (!$dom = domxml_open_file($comment_file)) {
		$warn .= '<li>'.sprintf(__('Could not parse the XML in comment file "%s".'), $comment_file).'</li>';
		return;
	}

	$root = $dom->document_element();
	$title = pyblosxom_unhtmlspecialchars(pyblosxom_getCommentElement($root, "title", $comment_file));
	$author = pyblosxom_unhtmlspecialchars(pyblosxom_getCommentElement($root, "author", $comment_file));
	$link = pyblosxom_unhtmlspecialchars(pyblosxom_getCommentElement($root, "link", $comment_file));
	$link = preg_replace('|^http://|', '', $link);
	$pubDate = pyblosxom_getCommentElement($root, "pubDate", $comment_file);
	$description = pyblosxom_unhtmlspecialchars(pyblosxom_getCommentElement($root, "description", $comment_file));

	$description = '<p>'.$description.'</p>';

	if ($tidy) {
		tidy_setopt('output-xhtml', true);
		tidy_setopt('show-body-only', true);
		tidy_setopt('output-encoding', dc_encoding);
		tidy_parse_string($description);
		tidy_clean_repair();
		$description = tidy_get_output();
	}

	$insReq = 'INSERT INTO '.$blog->t_comment.' '.
		'(post_id,comment_dt,comment_upddt,comment_auteur,comment_email,'.
		'comment_site,comment_content,comment_ip,comment_pub,'.
		'comment_trackback) VALUES '.
		'(\''.$blog->con->escapeStr($post_id).'\', '.
		'FROM_UNIXTIME(\''.  $pubDate . '\'), '.
		'FROM_UNIXTIME(\''.  $pubDate . '\'), '.
		'\''.$blog->con->escapeStr($author).'\', '.
		'\'\', '.
		'\''.$blog->con->escapeStr($link).'\', '.
		'\''.$blog->con->escapeStr($description).'\', '.
		'\''.$blog->con->escapeStr("127.0.0.1").'\', '.
		'1,'. (integer) false.') ';
	if ($blog->con->execute($insReq) === false) {
		$blog->setError('MySQL : '.$blog->con->error(),2000);
		$err .= '<p>'.sprintf(__('During import of %s:'),
			$comment_file).'</p>'.$blog->error(true);
		$blog->resetError();
	}
}

function pyblosxom_listComments ($comments_dir = "", $rel_dir = "")
{
	global $warn;
	global $comments;

	if (is_dir($comments_dir . "/" . $rel_dir) == false) {
		$warn .= '<li>'.sprintf(__('"%s" is not a directory.'), $comments_dir . "/" . $rel_dir).'</li>';
		return;
	}

	$dh = opendir($comments_dir . "/" . $rel_dir);
	if ($dh == false) {
		$warn .= '<li>'.sprintf(__('Could not open directory "%s".'), $comments_dir . "/" . $rel_dir).'</li>';
		return;
	}

	while (($file = readdir($dh)) !== false) {
		if ($file == "." || $file == "..") {
			continue;
		}

		if (is_dir($comments_dir."/".$rel_dir."/".$file)) {
			pyblosxom_listComments($comments_dir, $rel_dir . "/" . $file);
			continue;
		}
		
		if (!preg_match("/\.cmt$/", $file)) {
			continue;
		}

		if (!preg_match("/-(\d+\.\d+)\.cmt$/", $file, $matches)) {
			continue;
		}
		$key = $matches[1];

		if (isset($comments[$key])) {
			$warn .= '<li>'.sprintf(__('Ignoring comment "%s" because there already is a comment created at the same time.'), $comments_dir."/".$rel_dir."/".$file).'</li>';
			continue;
		}

		$comments[$key] =
			array ("file" => $comments_dir."/".$rel_dir."/".$file,
			       "rel_file_no_ext" => $rel_dir."/".preg_replace("/-\d+\.\d+\.cmt$/", "", $file));
	}

	closedir($dh);
}

function pyblosxom_updateData ($mtime, $comments, $post_id, $lang, $file)
{
	global $err;
	global $blog;

	$updReq = 'UPDATE '.$blog->t_post.
		'  SET post_dt = FROM_UNIXTIME(\''.$mtime.'\') '.
		'    , post_creadt = FROM_UNIXTIME(\''.$mtime.'\') '.
		'    , post_upddt = FROM_UNIXTIME(\''.$mtime.'\') '.
		'    , post_lang = \''.$lang.'\' '.
		'    , nb_comment = \''.$comments.'\' '.
		' WHERE post_id = \''.$post_id.'\'';
	if ($blog->con->execute($updReq) === false) {
		$blog->setError('MySQL : '.$blog->con->error(),2000);
		$err .= '<p>'.sprintf(__('During update of %s:'),
			$file).'</p>'.$blog->error(true);
		$blog->resetError();
	}
}

function pyblosxom_createPost ($user_id = 0, $cat_id = 0, $file_name = "", $tidy = false)
{
	global $warn;
	global $blog;

	if (file_exists($file_name) == false) {
		$warn .= '<li>'.sprintf(__('Post file "%s" does not exist.'), $file_name).'</li>';
		return;
	}
	$handle = fopen($file_name, "r");
	if ($handle == false) {
		$warn .= '<li>'.sprintf(__('Could not open post "%s".'), $file_name).'</li>';
		return;
	}

	$title = fgets($handle);
	/* we need utf8_encode because html_entity_decode doesn't seem
	 * to work well if we ask to decode to UTF-8 */
	$title = utf8_encode(html_entity_decode(utf8_decode($title)));

	$contents = '';
	while (!feof($handle)) {
		$contents .= fread($handle, 8192);
	}
	fclose($handle);

	if ($tidy) {
		tidy_setopt('output-xhtml', true);
		tidy_setopt('show-body-only', true);
		tidy_setopt('output-encoding', dc_encoding);
		tidy_parse_string($contents);
		tidy_clean_repair();
		$contents = tidy_get_output();
	}

	return $blog->addPost($user_id, $title, NULL, NULL, $contents, NULL, $cat_id);
}

function pyblosxom_createCat ($cat_name = "")
{
	global $blog;
	global $con;
	global $err;

	if ($blog->addCat($cat_name) === false) {
		$err .= '<p>'.sprintf(__('Error while adding category "%s":'),
			$cat_name).'</p>'.$blog->error(true);
		$blog->resetError();

		return false;
	}

	if (($rs = $con->select('SELECT MAX(cat_id) AS cat_id FROM '.$blog->t_categorie)) === false) {
		$blog->setError('MySQL : '.$blog->con->error(),2000);
		$err .= '<p>'.sprintf(__('During selection of cat_id in table %s:'),
			$blog->t_categorie).'</p>'.$blog->error(true);
		$blog->resetError();

		return false;
	}
	if (!$rs->isEmpty()) {
		return $rs->field('cat_id');
	} else {
		return false;
	}
}

function pyblosxom_listPosts($data_dir = "", $rel_dir = "", $cat_name = "")
{
	global $warn;
	global $posts;
	global $categories;

	if (is_dir($data_dir . "/" . $rel_dir) == false) {
		$warn .= '<li>'.sprintf(__('"%s" is not a directory.'), $data_dir . "/" . $rel_dir).'</li>';
		return;
	}

	$dh = opendir($data_dir . "/" . $rel_dir);
	if ($dh == false) {
		$warn .= '<li>'.sprintf(__('Could not open directory "%s".'), $data_dir . "/" . $rel_dir).'</li>';
		return;
	}

	while (($file = readdir($dh)) !== false) {
		if ($file == "." || $file == "..") {
			continue;
		}

		if (is_dir($data_dir."/".$rel_dir."/".$file)) {
			pyblosxom_listPosts($data_dir, $rel_dir . "/" . $file, $file);
		} elseif (preg_match("/\.txt$/", $file)) {
			if (isset($posts[filemtime($data_dir."/".$rel_dir."/".$file)])) {
				$warn .= '<li>'.sprintf(__('Ignoring post "%s" because there already is a post with the same modified time.'), $data_dir."/".$rel_dir."/".$file).'</li>';
				continue;
			}
			$categories[filemtime($data_dir."/".$rel_dir."/".$file)] = $cat_name;
			$posts[filemtime($data_dir."/".$rel_dir."/".$file)] =
				array ("file" => $data_dir."/".$rel_dir."/".$file,
				       "rel_file_no_ext" => $rel_dir."/".preg_replace("/\.txt$/", "", $file),
				       "cat" => $cat_name);
		}
	}
	closedir($dh);
}

function pyblosxom_unhtmlspecialchars($string)
{
	return str_replace(array("&gt;", "&lt;", "&quot;", "&amp;"),
			   array(">", "<", "\"", "&"),
			   $string);
}

?>
