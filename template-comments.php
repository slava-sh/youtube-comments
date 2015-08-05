<?php
/**
* Comments template for "YouTube Comments" plugin
*/

if (1 == $start_index) { 
?>
	<h4 class='comments-heading'><strong>Comments</strong><?php if (empty($settings['post_comments'])): ?> - Visit YouTube to comment<?php endif ?></h4>
	<?php if (!empty($settings['post_comments'])) { ?>
	<div class='post-comment'>
		<div class='post-comment-success'>
			<p>Your comment has been posted and will appear soon</p>
		</div>
		<?php	
		if (empty($_SESSION['access-token'])) {
			$state = uniqid('', true);
			$this->client->setState($state);
			$_SESSION['state'] = $state;	
		?>
		<div class='post-comment-login'>
			<p><a href='<?php echo $this->client->createAuthUrl(); ?>'>Sign in</a> now to post a comment</p>
		</div>
		<?php } else { ?>
		<form method='post' class='post-comment-form'>
			<?php wp_nonce_field('post-comment','post-comment-nonce'); ?> 
			<textarea type='text' class='post-comment-text' name='comment' placeholder='Leave a comment'></textarea>
			<div class='post-comment-controls'>
				<span class='post-comment-count'>500</span> characters remaining.
				Click <a href="javascript://" class='post-comment-logout'>here</a> to sign out.
				<button type='submit' class='post-comment-submit youtube-button'>Post</button>
			</div>
		</form>			
		<?php } ?>
	</div>	
	<?php } ?>
<?php } ?>
	<ul class='comments-list'>
<?php 
foreach($commentThreads as $commentThread) { 
	$snippet = $commentThread->snippet->topLevelComment->snippet;
	$link_href = $snippet->authorChannelUrl;
	$thumbnail_url = $snippet->authorProfileImageUrl;
	$name = $snippet->authorDisplayName;
	$posted = $this->time_ago($snippet->publishedAt);
	$text = nl2br($snippet->textDisplay);
//	// Get reply
//	$reply_link = '';
//	foreach ($comment->link as $link) {
//		$atts = $link->attributes();
//		if (strpos($atts->rel, 'in-reply-to')) {
//			$reply = @simplexml_load_file($atts->href); // suppress errors
//			if (isset($reply->author)) {
//				$reply_link = sprintf("<a href='%s' title=''>%s</a>", $reply->author->uri, $reply->author->name);
//			}
//			break;
//		}			
//	}
?>
		<li class='comment-item'>
			<div class='author-thumbnail'>
				<a href='<?php echo $link_href; ?>' title='<?php echo $name; ?>'>
					<img src='<?php echo $thumbnail_url; ?>' alt='' />
				</a>
			</div>
			<div class='comment-content'>
				<span class='author-metadata'>
					<a class='author-name' href='<?php echo $link_href; ?>'><?php echo $name; ?></a>
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
<?php   if (!empty($nextPageToken)) { ?>
	<div class='comments-pagination'>
		<button type='button' class='show-more youtube-button'>Show more</button>
	</div>
<?php } // END
