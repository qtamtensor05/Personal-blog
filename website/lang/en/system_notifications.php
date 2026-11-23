<?php

return [
    'account_restricted' => ['title' => 'Account restriction applied', 'message' => 'A :kind restriction was applied to your account.'],
    'account_restriction_lifted' => ['title' => 'Account restriction lifted', 'message' => 'Your account restriction was lifted.'],
    'role_granted' => ['title' => 'Platform role granted', 'message' => 'Role #:role was granted to your account.'],
    'role_revoked' => ['title' => 'Platform role revoked', 'message' => 'Role #:role was removed from your account.'],
    'permission_updated' => ['title' => 'Permission updated', 'message' => 'Permission :permission was updated for your account.'],
    'moderation_review_started' => ['title' => 'Report under review', 'message' => 'Your report #:id is now being reviewed.'],
    'moderation_report_closed' => ['title' => 'Report closed', 'message' => 'Your report #:id was :status.'],
    'moderation_action_taken' => ['title' => 'Moderation action applied', 'message' => 'A moderation action (:action) was applied to :blog.'],
    'moderation_suspension_lifted' => ['title' => 'Blog suspension lifted', 'message' => 'The suspension on :blog was lifted.'],
    'post_liked' => ['title' => 'Your post received a like', 'message' => ':actor liked your post ":post".'],
    'post_comment_created' => ['title' => 'Your post has a new comment', 'message' => ':actor commented on your post ":post".'],
    'comment_replied' => ['title' => 'Someone replied to your comment', 'message' => ':actor replied to your comment.'],
    'kinds' => ['LOCK' => 'lock', 'SUSPEND' => 'suspension', 'DISABLE' => 'disablement'],
    'actions' => ['none' => 'no action', 'hide_comment' => 'hide comment', 'archive_post' => 'archive post', 'suspend_blog' => 'suspend blog'],
    'statuses' => ['RESOLVED' => 'resolved', 'DISMISSED' => 'dismissed'],
    'reason' => 'Reason: :reason',
    'open_comment' => 'View comment',
];
