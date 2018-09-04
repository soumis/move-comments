<?php

include_once('moco-common.php');

class WordPressModel
{
    public function getPostsWithStatus($postStatus="publish")
    {
        global $wpdb;
        $postStatus = htmlentities($postStatus);
		$data = $wpdb->get_results("select id, post_title 
                    from {$wpdb->posts}
                    where post_type in ('page', 'post')
                    and post_status ='$postStatus'
                    order by id desc");
		return $data;
    }

    /**
     * @return array|null|object
     */
    public function getsPostsWithComments()
	{
		global $wpdb;
		$data = array();
		
		$data = $wpdb->get_results("select id, post_title
					from {$wpdb->posts}
					where post_type in ('page', 'post')
					and comment_count > 0
					order by id desc");
		return $data;
	}

	public function getPostTitleByID($id)
	{
		global $wpdb;
		$data = array();
		if(is_numeric($id))
		{		
			$data = $wpdb->get_var("select post_title
						from {$wpdb->posts}
						where id = $id");
		}
		return $data;
	}

	public function getCommentsForPostID($id)
	{
		global $wpdb;
		$data = array();

		if(is_numeric($id))
		{		
			$data = $wpdb->get_results("select comment_id, comment_author, 
						comment_date, comment_content
						from {$wpdb->comments}
						where comment_post_id = $id
						order by comment_id desc");
		}
		return $data;
	}

	public function moveComment($source_post_id, $target_post_id, $comment_id)
	{
		global $wpdb;
		
		// update the comment_post_id to $target_post_id
		$sql[] = "update {$wpdb->comments}
				set comment_post_id = $target_post_id
				where comment_id = $comment_id";

		//Decrement the comment_count in the $source_post_id
		$sql[] = "update {$wpdb->posts}
				set comment_count = comment_count-1
				where id = $source_post_id";
				
		// Increment the comment_count in the $target_post_id
		$sql[] = "update {$wpdb->posts}
				set comment_count = comment_count+1
				where id = $target_post_id";
		
		foreach($sql as $query)
		{
			$wpdb->query($query);
		}
	}
	
}
?>