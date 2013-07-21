/* 
* Script for "YouTube Comments" Wordpress plugin
*/

jQuery(document).ready(function($) {

	var startIndex = 1;

	// Get comments via AJAX
	function showMoreComments() {
		var data = {
			action: 'get_comments',
			videoID: youtubeComments.videoID,
			startIndex: startIndex
		};
		$.post(youtubeComments.ajaxURL, data, function(response) { 
			$('.comments-loading, .comments-pagination').hide();
			$('#youtube-comments').append(response);
			startIndex = startIndex + parseInt(youtubeComments.results);
		});
	}
	
	function refreshComments() {
		$('.comments-heading, .post-comment, .comments-list, .comments-pagination').remove();
		$('.comments-loading').show();
		startIndex = 1;
		showMoreComments();	
	}

	// Get comments when button clicked
	$(document).on('click', 'button.show-more', function(event) {
		var $loading = $('.comments-loading').detach();
		$('#youtube-comments').append($loading);
		$('.comments-pagination').remove();
		$('.comments-loading').show();
		showMoreComments();
	});
	
	// Handle post comment
	$(document).on('submit', 'form.post-comment-form', function(event) {
		event.preventDefault();
		var comment = $('.post-comment-text').val();
		if ($.trim(comment) == '') return false;
		var data = {
			action: 'post_comment',
			videoID: youtubeComments.videoID,
			comment: comment,
			nonce: $('#post-comment-nonce').val()
		};		
		$.post(youtubeComments.ajaxURL, data, function(response) { 
			refreshComments();
		});
	});
	
	// Post comment count
	$(document).on('keyup', 'textarea.post-comment-text', function(event) {
		var value = $('.post-comment-text').val();
		if (value.length >= 500) {
			$('.post-comment-text').val(value.substring(0, 500));
			$('.post-comment-count').text(0);
		} else
			$('.post-comment-count').text(500 - value.length);
	});
	
	// Logout link
	$(document).on('click', 'span.post-comment-logout', function(event) {
		var data = { 
			action: 'post_logoff',
			nonce: $('#post-comment-nonce').val()		
		};
		$.post(youtubeComments.ajaxURL, data, function(response) { 
			refreshComments();
		});
	});

	// Get initial comments
	showMoreComments();

});