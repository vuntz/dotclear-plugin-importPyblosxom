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

require dirname(__FILE__).'/pyblosxom.php';

if (!isset($_SESSION['importPyblosxom_step']) ||
    $_SESSION['importPyblosxom_step'] < 1 ||
    $_SESSION['importPyblosxom_step'] > 2) {
	$_SESSION['importPyblosxom_step'] = 1;
}

buffer::str('<h2>'.__('pyblosxom import').' ('.$_SESSION['importPyblosxom_step'].'/2)</h2>');

if ($_SESSION['importPyblosxom_step'] == 1 &&
    (!isset($_POST['action']) ||
     (
      (!isset($_POST['user_id']) || $_POST['user_id'] == '') ||
      (!isset($_POST['rootcat']) || $_POST['rootcat'] == '') ||
      (!isset($_POST['datadir']) || $_POST['datadir'] == '' || !is_dir($_POST['datadir']))
     )
    )) {
	buffer::str(
	'<p>'.__('This tool will import your pyblosxom data to DotClear.').'</p>'
	);

	$err = '';

	if (isset($_POST['action']) &&
	    (!isset($_POST['user_id']) || $_POST['user_id'] == '')) {
		$err .= __('No user specified').'<br />';
	}

	if (isset($_POST['action']) &&
	    (!isset($_POST['rootcat']) || $_POST['rootcat'] == '')) {
		$err .= __('No root category specified').'<br />';
	}

	if (isset($_POST['action']) &&
	    (!isset($_POST['datadir']) || $_POST['datadir'] == '')) {
		$err .= __('No data directory specified').'<br />';
	} elseif (isset($_POST['action']) && !is_dir($_POST['datadir'])) {
		$err .= __('Specified data directory does not exist').'<br />';
	}

	if ($err != '') {
		buffer::str(
		'<div class="erreur"><p><strong>'.__('Error(s)').'</strong>'.
		'</p><p>'.$err.'</p></div>'
		);
	}

	buffer::str(
	'<form action="tools.php?p=importPyblosxom" enctype="multipart/form-data" '.
	'method="post">'.
	'<input type="hidden" name="action" value="submit" />'
	);

	buffer::str(
	'<p class="field"><label for="user_id" class="float">'.
	__('User who will be author of all posts:').
	'</label><select name="user_id" id="user_id">'
	);

	$rsUser = $blog->getUser();
	while(!$rsUser->EOF())
	{
		if (isset($_POST['user_id']) &&
		    $_POST['user_id'] == $rsUser->field('user_id')) {
			$selected = 'selected="selected"';
		} else {
			$selected = '';
		}

		buffer::str(
		'<option value="'.$rsUser->field('user_id').'" '.$selected.'>'.
		$rsUser->field('user_prenom').' '.$rsUser->field('user_nom').
		'</option>'
		);

		$rsUser->moveNext();
	}
	buffer::str(
	'</select>'
	);

	if (isset($_POST['rootcat'])) {
		$value = $_POST['rootcat'];
	} else {
		$value = '';
	}
	buffer::str(
	'<p class="field"><label for="rootcat" class="float">'.
	__('Category name for the pyblosxom root category:').
        '</label><input type="text" name="rootcat" '.
	'id="rootcat" value="'.$value.'" /></p>'
	);

	if (isset($_POST['datadir'])) {
		$value = $_POST['datadir'];
	} else {
		$value = '';
	}
	buffer::str(
	'<p class="field"><label for="datadir" class="float">'.
	__('pyblosxom data directory:').
        '</label><input type="text" name="datadir" '.
	'id="datadir" value="'.$value.'" /></p>'
	);

	if (isset($_POST['commentsdir'])) {
		$value = $_POST['commentsdir'];
	} else {
		$value = '';
	}
	buffer::str(
	'<p class="field"><label for="commentsdir" class="float">'.
	__('pyblosxom comments directory (usually, it\'s in the '.
	'<code>comments</code> subdirectory):').
        '</label><input type="text" name="commentsdir" '.
	'id="commentsdir" value="'.$value.'" /></p>'
	);

	if (isset($_POST['lang'])) {
		$value = $_POST['lang'];
	} else {
		$value = '';
	}
	buffer::str(
	'<p class="field"><label for="lang" class="float">'.
	__('Language for the posts:').
        '</label><input type="text" name="lang" '.
	'id="lang" value="'.$value.'" /></p>'
	);

	if (isset($_POST['deleteall']) && $_POST['deleteall'] == true) {
		$checked = 'checked="checked"';
	} else {
		$checked = '';
	}
	buffer::str(
	'<p class="field">'.
	'<input type="checkbox" id="deleteall" name="deleteall" value="true" '.
	$checked.' /><label class="inline" for="deleteall">'.
	__('Delete the already existing DotClear categories, posts and comments.').
	'</label></p>'
	);

	if (isset($_POST['redirect']) && $_POST['redirect'] == true) {
		$checked = 'checked="checked"';
	} else {
		$checked = '';
	}
	buffer::str(
	'<p class="field">'.
	'<input type="checkbox" id="redirect" name="redirect" value="true" '.
	$checked.' /><label class="inline" for="redirect">'.
	__('Write <code>RedirectPermanent</code> rules for an Apache configuration').
	'</label></p>'
	);

	if (function_exists('tidy_parse_string')) {
		if (isset($_POST['tidy']) && $_POST['tidy'] == true) {
			$checked = 'checked="checked"';
		} else {
			$checked = '';
		}
		buffer::str(
		'<p class="field">'.
		'<input type="checkbox" id="tidy" name="tidy" value="true" '.
		$checked.' /><label class="inline" for="tidy">'.
		__('Tidy HTML code.').
		'</label></p>'
		);
	} else {
		buffer::str(
		'<p>'.__('The "tidy" library is not available, you will not '.
		'be able to clean the HTML code.').
		'</p>'
		);
	}
	buffer::str(
	'<p class="field"><input class="submit" type="submit" value="'.
	__('Begin import').'" /></p>'.
	'</form>');

/***
 * Step 1: Remembering the values
 */
} elseif ($_SESSION['importPyblosxom_step'] == 1) {
	$_SESSION['importPyblosxom_user_id'] = $_POST['user_id'];
	$_SESSION['importPyblosxom_rootcat'] = $_POST['rootcat'];
	$_SESSION['importPyblosxom_datadir'] = $_POST['datadir'];
	if (isset($_POST['commentsdir'])) {
		$_SESSION['importPyblosxom_commentsdir'] = $_POST['commentsdir'];
	} else {
		$_SESSION['importPyblosxom_commentsdir'] = '';
	}
	if (isset($_POST['lang']) && $_POST['lang'] != '') {
		$_SESSION['importPyblosxom_lang'] = $_POST['lang'];
	} else {
		$_SESSION['importPyblosxom_lang'] = 'en';
	}
	if (isset($_POST['deleteall']) && $_POST['deleteall'] == true) {
		$_SESSION['importPyblosxom_deleteall'] = true;
	} else {
		$_SESSION['importPyblosxom_deleteall'] = false;
	}
	if (isset($_POST['redirect']) && $_POST['redirect'] == true) {
		$_SESSION['importPyblosxom_redirect'] = true;
	} else {
		$_SESSION['importPyblosxom_redirect'] = false;
	}
	if (isset($_POST['tidy']) && $_POST['tidy'] == true) {
		$_SESSION['importPyblosxom_tidy'] = true;
	} else {
		$_SESSION['importPyblosxom_tidy'] = false;
	}

	$_SESSION['importPyblosxom_step']++;
	header('Location: tools.php?p=importPyblosxom');

/***
 * Step 2: Putting everything in database
 */
} else if ($_SESSION['importPyblosxom_step'] == 2 && !isset($_POST['action'])) {
	$err = '';
	$warn = '';
	$redirectCats = '';
	$redirectPosts = '';
	$categories = array();
	$created_categories = array();
	$posts = array();
	$created_posts = array();
	$comments = array();

	if (isset($_SESSION['importPyblosxom_deleteall']) &&
	    $_SESSION['importPyblosxom_deleteall'] == true) {
		if ($con->execute('DELETE FROM '.$blog->t_categorie) === false) {
			$blog->setError('MySQL : '.$blog->con->error(),2000);
			$err .= '<p>'.sprintf(__('During deletion of table %s:'),
				$blog->t_categorie).'</p>'.$blog->error(true);
			$blog->resetError();
		}
		if ($con->execute('ALTER TABLE '.$blog->t_categorie.' AUTO_INCREMENT = 0') === false) {
			$blog->setError('MySQL : '.$blog->con->error(),2000);
			$err .= '<p>'.sprintf(__('During increment reset of table %s:'),
				$blog->t_categorie).'</p>'.$blog->error(true);
			$blog->resetError();
		}
		if ($con->execute('DELETE FROM '.$blog->t_post) === false) {
			$blog->setError('MySQL : '.$blog->con->error(),2000);
			$err .= '<p>'.sprintf(__('During deletion of table %s:'),
				$blog->t_post).'</p>'.$blog->error(true);
			$blog->resetError();
		}
		if ($con->execute('ALTER TABLE '.$blog->t_post.' AUTO_INCREMENT = 0') === false) {
			$blog->setError('MySQL : '.$blog->con->error(),2000);
			$err .= '<p>'.sprintf(__('During increment reset of table %s:'),
				$blog->t_post).'</p>'.$blog->error(true);
			$blog->resetError();
		}
		if ($con->execute('DELETE FROM '.$blog->t_comment) === false) {
			$blog->setError('MySQL : '.$blog->con->error(),2000);
			$err .= '<p>'.sprintf(__('During deletion of table %s:'),
				$blog->t_comment).'</p>'.$blog->error(true);
			$blog->resetError();
		}
		if ($con->execute('ALTER TABLE '.$blog->t_comment.' AUTO_INCREMENT = 0') === false) {
			$blog->setError('MySQL : '.$blog->con->error(),2000);
			$err .= '<p>'.sprintf(__('During increment reset of table %s:'),
				$blog->t_comment).'</p>'.$blog->error(true);
			$blog->resetError();
		}
	}

	pyblosxom_listPosts($_SESSION['importPyblosxom_datadir'], "", $_SESSION['importPyblosxom_rootcat']);

	if (isset($_SESSION['importPyblosxom_commentsdir']) &&
	    $_SESSION['importPyblosxom_commentsdir'] != '') {
		pyblosxom_listComments($_SESSION['importPyblosxom_commentsdir'], "");
	}

	ksort($categories);
	reset($categories);
	while (list($key, $val) = each($categories)) {
		if (!isset($created_categories[$val])) {
			$id = pyblosxom_createCat($val);
			if ($id !== false) {
				$created_categories[$val] = $id;

				if ($_SESSION['importPyblosxom_redirect']) {
					$rs = $blog->getCat($id);
					if (!$rs->EOF()) {
						$redirectCats .= 'RedirectPermanent \''.dc_blog_url.$val.'\' http://'.$_SERVER['SERVER_NAME'].dc_blog_url.$rs->f('cat_libelle_url').'
';
					}
				}
			} else {
				$warn .= '<li>'.sprintf(__('Could not create category "%s".'), $val).'</li>';
			}
		}
	}
	unset($categories);

	ksort($posts);
	reset($posts);
	while (list($key, $val) = each($posts)) {
		if (!isset($created_categories[$val["cat"]])) {
			$warn .= '<li>'.sprintf(__('Could not create the post corresponding to "%s" because the corresponding category ("%s") does not exist.'), $val["file"], $val["cat"]).'</li>';
			continue;
		}

		$id = pyblosxom_createPost($_SESSION['importPyblosxom_user_id'], $created_categories[$val["cat"]], $val["file"], $_SESSION['importPyblosxom_tidy']);
		if ($id === false) {
			$warn .= '<li>'.sprintf(__('Could not create the post corresponding to "%s".'), $val["file"]).'</li>';
			continue;
		}
		
		$created_posts[$val["rel_file_no_ext"]] =
			array ( "post_id" => $id,
				"mtime" => $key,
				"comments" => 0);

		if ($_SESSION['importPyblosxom_redirect']) {
			$rs = $blog->getPostByID($id);
			if (!$rs->EOF()) {
				$redirectPosts .= 'RedirectPermanent \''.dc_blog_url.preg_replace('/^\//', '', $val["rel_file_no_ext"]).'.html\' http://'.$_SERVER['SERVER_NAME'].$rs->getPermURL().'
';
			}
		}
	}
	unset($posts);

	if (isset($_SESSION['importPyblosxom_commentsdir']) &&
	    $_SESSION['importPyblosxom_commentsdir'] != '') {
		ksort($comments);
		reset($comments);
		while (list($key, $val) = each($comments)) {
			if (!isset($created_posts[$val["rel_file_no_ext"]])) {
				$warn .= '<li>'.sprintf(__('Could not create the comment corresponding to "%s" because the corresponding post (%s) does not exist.'), $val["file"], $val["rel_file_no_ext"]).'</li>';
				continue;
			}

			pyblosxom_createComment($created_posts[$val["rel_file_no_ext"]]["post_id"], $val["file"], $_SESSION['importPyblosxom_tidy']);
			$created_posts[$val["rel_file_no_ext"]]["comments"]++;
		}
	}
	unset($comments);

	reset($created_posts);
	while (list($key, $val) = each($created_posts)) {
		pyblosxom_updateData($val["mtime"], $val["comments"], $val["post_id"], $_SESSION['importPyblosxom_lang'], $key);
	}
	unset($created_posts);

	buffer::str(
	'<h3>'.__('Congratulations').'</h3>'.
	'<p>'.__('You have successfully imported your pyblosxom data in '.
	'DotClear.').'</p>'
	);
	if ($err != '' || $warn != '') {
		buffer::str('<h3>'.__('Report').'</h3>');
		if ($err != '') {
			buffer::str(
    			'<div class="erreur"><p><strong>'.__('Error(s)').
			' :</strong></p>'.$err.'</div>'
			);
		}
		if ($warn != '') {
			buffer::str(
			'<div class="erreur"><p><strong>'.__('Warning(s)').
			' :</strong></p>'.'<ul>'.$warn.'</ul></div>'
			);
		}
	}

	if ($_SESSION['importPyblosxom_redirect'] && ($redirectCats != '' || $redirectPosts != '')) {
		buffer::str('<h3>'.__('RedirectPermanent rules').'</h3>');
		/* the more general redirect rules should be at the end */
		buffer::str('<pre>'.$redirectPosts.$redirectCats.'</pre>');
	}

	buffer::str(
	'<form method="post" action="tools.php">'.
	'<p>'.
	'<input class="submit" type="submit" value="'.__('Back to tools').'"/>'.
	'</p></form>'
	);
/***
 * Clean up
 */
	unset($_SESSION['importPyblosxom_step']);
	unset($_SESSION['importPyblosxom_rootcat']);
	unset($_SESSION['importPyblosxom_datadir']);
	unset($_SESSION['importPyblosxom_commentsdir']);
	unset($_SESSION['importPyblosxom_lang']);
	unset($_SESSION['importPyblosxom_deleteall']);
	unset($_SESSION['importPyblosxom_redirect']);
	unset($_SESSION['importPyblosxom_tidy']);
} else {
	buffer::str(__('Internal error'));
}

?>
