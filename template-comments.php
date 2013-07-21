<?php
/**
* Comments template for "YouTube Comments" plugin
*/

$settings = get_option('yc_settings');
if ($this->start_index == 1) { 
?>
	<h4 class='comments-heading'><strong>All Comments</strong> (<?php echo $count; ?>)</h4>	
	<?php if (!empty($settings['post_comments'])) { ?>
	<div class='post-comment'>
		<?php	
		if (empty($_SESSION['access-token'])) {
			$state = uniqid('', true);
			$this->client->setState($state);
			$_SESSION['state'] = $state;	
		?>
		<div class='post-comment-login'>
			<p><a href='<?php echo $this->client->createAuthUrl(); ?>'>Sign in</a> now to post a comment!<p>	
		</div>
		<?php } else { ?>
		<form method='post' class='post-comment-form'>
			<?php wp_nonce_field('post-comment','post-comment-nonce'); ?> 
			<textarea type='text' class='post-comment-text' name='comment' placeholder='Leave a comment'></textarea>
			<div class='post-comment-controls'>
				<span class='post-comment-count'>500</span> characters remaining.
				Click <span class='post-comment-logout'>here</span> to sign out.
				<button type='submit' class='post-comment-submit youtube-button'>Post</button>
			</div>
		</form>			
		<?php } ?>
	</div>	
	<?php } ?>
<?php } ?>
	<ul class='comments-list'>
<?php 
foreach($comments->entry as $comment) { 
	// Get author metadata
	$author_uri = $comment->author->uri;
	$author = simplexml_load_file($author_uri);
	$author_link = $author->link->attributes();
	$media = $author->children('media', true);
	$thumbnail = $media->thumbnail->attributes();
	$posted = $this->time_ago($comment->published);
	$text = nl2br($comment->content);
	// Get reply
	$reply_link = '';
	foreach ($comment->link as $link) {
		$atts = $link->attributes();
		if (strpos($atts->rel, 'in-reply-to')) {
			$reply = @simplexml_load_file($atts->href); // suppress errors
			if (isset($reply->author)) 
				$reply_link = sprintf("<a href='%s' title=''>%s</a>", $reply->author->uri, $reply->author->name);
			break;
		}			
	}
?>
		<li class='comment-item'>
			<div class='author-thumbnail'>
				<img src='<?php echo $thumbnail->url; ?>' alt='' />
			</div>
			<div class='comment-content'>
				<span class='author-metadata'>
					<a class='author-name' href='<?php echo $author_link->href; ?>'><?php echo $comment->author->name; ?></a>
					<span class='comment-posted'><?php echo $posted; ?></span>
				</span>
				<div class='comment-text'>
					<?php echo $text; ?>
				</div>
<?php if (!empty($reply_link)) { ?>				
				<div class='comment-reply'>in reply to <?php echo $reply_link; ?></div>
<?php } ?>
			</div>
		</li>
<?php } ?>
	</ul>
	<div class='comments-pagination'>
		<button type='button' class='show-more youtube-button'>Show more</button>
	</div>