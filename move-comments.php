<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

include_once('moco-db.php');
include_once('moco-common.php');

class MoveComments
{
	private $db;
	private $form_errors;
	private $helper;

	function __construct()
	{
		$this->db = new WordPressModel();

		$this->helper = new MocoHelper();
		
		$this->attach_view();


        /* Sanitize $_POST to prevent XSS. */
        if($_POST)
        {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        }

        // Validate data for processing
		if($_POST and $this->validateForm($_POST))
		{
			$this->processPostSubmission($_POST);
		}
	}

	function processPostSubmission(&$data)
	{
		if($data and is_array($data))
		{
			$source_post_id = (int) $data['source_post_id'];
			$target_post_id = (int) $data['target_post_id'];
			foreach($data['move_comment_id'] as $comment_id)
			{
                $comment_id = (int) $comment_id;
				$this->db->moveComment($source_post_id, $target_post_id, $comment_id);
			}
		}
        $this->helper->redirect();
	}
	
	function validateForm(&$data)
	{
		$validate = true;
		
		if($data['target_post_id'] == 0)
		{
			$this->form_errors['target_post_id'] = 'Please select a post';
			$validate = false;
		}
		elseif($data['target_post_id'] == $data['source_post_id'])
		{
			$this->form_errors['target_post_id'] = 'You are trying to move the comments to the same post.';
			$validate = false;
		}
		
		return $validate;
	}
	
	function attach_view()
	{
		// Has the use access to moderate comments?
//		if(current_user_can('moderate_comments'))
//		{	
			// Add Admin Menu
			add_action('admin_menu', array(&$this, 'adminMenu'));
//		}
	}
	
	// Manage Admin Options
	function adminMenu()
	{
		global $submenu;
		
		// Attach the GUI under 'Comments' otherwise set it under 'Management' 
		if (isset($submenu['edit-comments.php']))
		{
			// parent, page_title, menu_title, access_level/capability, file, [function]);
			add_submenu_page('edit-comments.php', 'Move Comments', 'Move', 8, __FILE__, array(&$this, 'adminPage'));
		}
		else
		{
			// Attach the admin page under Management
			add_management_page('Move Comments', 'Move Comments', 8, __FILE__, array(&$this, 'adminPage'));

		}
	}	

	// Admin page
	function adminPage()
	{
		$html = '<div class="wrap">';
		$html .= '<h2>Move Comments</h2>';

		$html .= $this->displayAdminForm();

  		// Debug Screen
// 		$html .= $this->debug_section();
		
		$html .= '</div>';
		
		print($html);
	}

    /**
     * @return string
     */
    function displayAdminForm()
	{
        /* Sanitize $_GET to prevent XSS. */
        if($_GET)
        {
            $_GET = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
        }

        $action = htmlspecialchars($_SERVER['PHP_SELF']);
        $page = htmlspecialchars($_REQUEST['page']);
        $sourcePostId = htmlspecialchars($_GET['source_post_id']);

        // Display the Source Post/Page selection
		$html = $this->displayPostFilter();

		$html .= '<br /><br />';

		$html .= '<form name="move-comments" method="post" action="'.$action.'?page='.$page.'&source_post_id='.$sourcePostId.'">';

//		$html .= '<p class="submit"><input type="submit" value="Update Options  &raquo;"></p>';
		
		if($sourcePostId and is_numeric($sourcePostId))
		{
			$html .= $this->displayComments($sourcePostId);
		}
/*		else
		{
			$html .= '<p>Select a post to browse its comments.</p><br />';
		}
*/
        // Display the Destination Post/Page selection
		$html .= $this->displayDestinationPost();
		
		$html .= '<br /><br />';
		
		// Hidden form attribute for source_post_id
		$html .= '<input type="hidden" name="source_post_id" value="'.$sourcePostId.'">';
		
		// Submit button
		$html .= '<p class="submit"><input type="submit" value="Move Comment &raquo;"></p>';
		$html .= '</form>';
	
		return $html;
	}

	// Display post filtering
	function displayPostFilter()
	{
		$html = '';
		$id = (int)$_REQUEST['source_post_id'];
		$posts = $this->db->getsPostsWithComments();
		
		if(!empty($posts))
		{
			$html = 'View comment(s) in post/page: ';
			$html .= "<select name=\"source_post_id\" onchange=\"javascript:location.href='?page=move-comments/move-comments.php&source_post_id='+this.options[this.selectedIndex].value;\">";

			$s = 0;
			if($id == 0)
			{
				$s = 'selected';
			}
			$html .= '<option value="0" '.$s.'>-- Select Source --</option>';
			
			foreach($posts as $p)
			{
				$s = "";
				if($id == $p->id)
				{
					$s = "selected";
				}
				$html .= "<option value=\"$p->id\" $s>$p->post_title</option>";
			}
			$html .= '</select>';
			
			if($id)
			{
				// $this->db->getPostTitleByID($id);
				$html .= " <a href=\"".get_permalink($id)."\" target=\"_blank\">View</a>";
			}
			
		}
		return $html;
	}
	
	function displayComments($post_id)
	{
		$comments = array();
		$html = '';
		
		if(is_numeric($post_id))
		{
			$comments = $this->db->getCommentsForPostID($post_id);
		}
		
		if(!empty($comments))
		{
			// List the available pages and posts in the database
			$html .= '<table id="the-list-x" width="100%" cellpadding="3" cellspacing="3">'."\n";
			$html .= '<tr>'."\n";
//			$html .= '<th scope="col">ID</th>'."\n";
            $html .= '<th scope="col">Select</th>'."\n";
			$html .= '<th scope="col">Commenter</th>'."\n";
			$html .= '<th scope="col">Comment</th>'."\n";
			$html .= '<th scope="col">Dated</th>'."\n";

			$html .= '<tr>'."\n";
			
			$checkbox_index = 0;
			foreach($comments as $comment)
			{
			    // Row Definition
				if($this->helper->is_even($checkbox_index))
				{
					$row_class = "alternate";
				}
				else
				{
					$row_class = "";
				}
				$html .= "<tr id=\"$comment->comment_id\" class=\"$row_class\">\n";

				// Row Columns
                // Display the comment entry as checked if the validation fails and user had it checked upon form submission
   //             $move_comment_id = html_escape($_POST["move_comment_id"]);
                if($_POST["move_comment_id"] and $_POST["move_comment_id"][$checkbox_index] == $comment->comment_id)
                {
                    $checked = 'checked';
                }
                else
                {
                    $checked = '';
                }

                $html .= "<td><input type=\"checkbox\" name=\"move_comment_id[$checkbox_index]\" value=\"$comment->comment_id\" $checked /></td>\n";
//				$html .= "<td>$comment->comment_id</td>\n";
				$html .= "<td>$comment->comment_author</td>\n";


				// Display a portion of the comment_content if it is too long
				$comment_body = $comment->comment_content;
				if(strlen($comment_body) > 250)
				{
					$comment_body = substr($comment->comment_content, 0, 250);
					$comment_body .= ' [&#8230;]';
				}

				$html .= "<td>$comment_body</td>\n";
				$html .= "<td>$comment->comment_date</td>\n";


				$html .= '</tr>';
				$checkbox_index++;
			}
			$html .= '</table>'."\n";
			$html .= '<br />'."\n";
		}
/*		else
		{
			$html .= '<p><strong>There are no comments in this post.</strong></p><br />'."\n";
		}
*/
		return $html;
	}
	
	// Display post filtering
	function displayDestinationPost()
	{
		$html = '';

		// $posts = $this->db->get_all_posts();
        $posts = $this->db->getPostsWithStatus("publish");

		if(!empty($posts))
		{
			$html .= 'Move comment(s) to published post: '."\n";
			$html .= "<select name=\"target_post_id\">\n";

			$html .= '<option value="0">-- Select Destination --</option>'."\n";
			
			foreach($posts as $p)
			{
				$sel = 0;
				if($_POST['target_post_id'] == $p->id)
				{
					$sel = 'selected';
				}
				$html .= "<option value=\"$p->id\" $sel>$p->post_title</option>\n";
			}
			$html .= '</select>'."\n";
		}
		else
        {
            $html .= 'No published page or post exists'."\n";
        }
		
		if($this->form_errors['target_post_id'])
		{
			$html .= '<strong style="color:red;"> <- '.$this->form_errors['target_post_id'].'</strong>'."\n";
		}
		
		return $html;
	}
}

$mc = new MoveComments();
?>