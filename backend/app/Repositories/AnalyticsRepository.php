<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsRepository
{
    /**
     * Get user statistics with role-based grouping
     */
    public function getUserStatistics($startDate = null, $endDate = null)
    {
        $query = DB::table('users')
            ->join('roles', 'users.Role', '=', 'roles.RoleName')
            ->select(
                'roles.RoleName as role',
                'roles.RoleDescription as role_description',
                DB::raw('COUNT(*) as user_count'),
                DB::raw('COUNT(CASE WHEN users.Status = "active" THEN 1 END) as active_users'),
                DB::raw('COUNT(CASE WHEN users.Status = "banned" THEN 1 END) as banned_users'),
                DB::raw('COUNT(CASE WHEN users.Status = "pending" THEN 1 END) as pending_users'),
                DB::raw('AVG(CASE WHEN users.LastLoginAt IS NOT NULL THEN DATEDIFF(NOW(), users.LastLoginAt) END) as avg_days_since_login')
            );

        if ($startDate) {
            $query->where('users.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('users.created_at', '<=', $endDate);
        }

        return $query->groupBy('roles.RoleName', 'roles.RoleDescription')
                    ->orderBy('user_count', 'desc')
                    ->get();
    }

    /**
     * Get post engagement metrics using complex JOINs
     */
    public function getPostEngagementMetrics($limit = 20)
    {
        return DB::table('posts as main_post')
            ->leftJoin('posts as replies', 'main_post.PostID', '=', 'replies.ParentPostID')
            ->join('users as authors', 'main_post.Author', '=', 'authors.UID')
            ->join('roles', 'authors.Role', '=', 'roles.RoleName')
            ->leftJoin('tag_is_used', 'main_post.PostID', '=', 'tag_is_used.PostID')
            ->where('main_post.ParentPostID', null) // Only main posts, not replies
            ->where('main_post.Status', 'published')
            ->select(
                'main_post.PostID as post_id',
                'main_post.Topic as title',
                'authors.Username as author',
                'roles.RoleName as author_role',
                'main_post.created_at',
                DB::raw('CHAR_LENGTH(main_post.Content) as content_length'),
                DB::raw('COUNT(DISTINCT replies.PostID) as reply_count'),
                DB::raw('COUNT(DISTINCT replies.Author) as unique_repliers'),
                DB::raw('COUNT(DISTINCT tag_is_used.TagName) as tag_count'),
                DB::raw('COALESCE(MAX(replies.created_at), main_post.created_at) as last_activity'),
                DB::raw('DATEDIFF(COALESCE(MAX(replies.created_at), main_post.created_at), main_post.created_at) as discussion_span_days')
            )
            ->groupBy(
                'main_post.PostID', 
                'main_post.Topic', 
                'authors.Username', 
                'roles.RoleName',
                'main_post.created_at',
                'main_post.Content'
            )
            ->orderBy('reply_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get tag popularity with author diversity metrics
     */
    public function getTagPopularityMetrics()
    {
        return DB::table('tags')
            ->join('tag_is_used', 'tags.TagName', '=', 'tag_is_used.TagName')
            ->join('posts', 'tag_is_used.PostID', '=', 'posts.PostID')
            ->join('users', 'posts.Author', '=', 'users.UID')
            ->join('roles', 'users.Role', '=', 'roles.RoleName')
            ->where('posts.Status', 'published')
            ->select(
                'tags.TagName as tag_name',
                'tags.Description as tag_description',
                DB::raw('COUNT(posts.PostID) as total_posts'),
                DB::raw('COUNT(DISTINCT posts.Author) as unique_authors'),
                DB::raw('COUNT(CASE WHEN roles.RoleName = "admin" THEN 1 END) as admin_posts'),
                DB::raw('COUNT(CASE WHEN roles.RoleName = "moderator" THEN 1 END) as moderator_posts'),
                DB::raw('COUNT(CASE WHEN roles.RoleName = "member" THEN 1 END) as member_posts'),
                DB::raw('AVG(CHAR_LENGTH(posts.Content)) as avg_content_length'),
                DB::raw('MIN(posts.created_at) as first_used'),
                DB::raw('MAX(posts.created_at) as last_used'),
                DB::raw('COUNT(posts.PostID) / COUNT(DISTINCT posts.Author) as posts_per_author')
            )
            ->groupBy('tags.TagName', 'tags.Description')
            ->having('total_posts', '>', 0)
            ->orderBy('total_posts', 'desc')
            ->get();
    }

    /**
     * Get user activity timeline with aggregated metrics
     */
    public function getUserActivityTimeline($period = '30days')
    {
        $days = match($period) {
            '7days' => 7,
            '30days' => 30,
            '90days' => 90,
            '1year' => 365,
            default => 30
        };

        $startDate = Carbon::now()->subDays($days);

        // Get daily post creation metrics
        $dailyPosts = DB::table('posts')
            ->join('users', 'posts.Author', '=', 'users.UID')
            ->join('roles', 'users.Role', '=', 'roles.RoleName')
            ->where('posts.created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(posts.created_at) as date'),
                DB::raw('COUNT(*) as total_posts'),
                DB::raw('COUNT(DISTINCT posts.Author) as unique_authors'),
                DB::raw('COUNT(CASE WHEN roles.RoleName = "admin" THEN 1 END) as admin_posts'),
                DB::raw('COUNT(CASE WHEN roles.RoleName = "moderator" THEN 1 END) as moderator_posts'),
                DB::raw('COUNT(CASE WHEN roles.RoleName = "member" THEN 1 END) as member_posts'),
                DB::raw('AVG(CHAR_LENGTH(posts.Content)) as avg_content_length')
            )
            ->groupBy(DB::raw('DATE(posts.created_at)'))
            ->orderBy('date', 'desc')
            ->get();

        // Get daily user registrations
        $dailyRegistrations = DB::table('users')
            ->join('roles', 'users.Role', '=', 'roles.RoleName')
            ->where('users.created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(users.created_at) as date'),
                DB::raw('COUNT(*) as new_users'),
                DB::raw('COUNT(CASE WHEN roles.RoleName = "admin" THEN 1 END) as new_admins'),
                DB::raw('COUNT(CASE WHEN roles.RoleName = "moderator" THEN 1 END) as new_moderators'),
                DB::raw('COUNT(CASE WHEN roles.RoleName = "member" THEN 1 END) as new_members')
            )
            ->groupBy(DB::raw('DATE(users.created_at)'))
            ->get()
            ->keyBy('date');

        // Merge the data
        return $dailyPosts->map(function ($item) use ($dailyRegistrations) {
            $registrationData = $dailyRegistrations->get($item->date);
            
            $item->new_users = $registrationData->new_users ?? 0;
            $item->new_admins = $registrationData->new_admins ?? 0;
            $item->new_moderators = $registrationData->new_moderators ?? 0;
            $item->new_members = $registrationData->new_members ?? 0;
            
            return $item;
        });
    }

    /**
     * Get comprehensive content analysis
     */
    public function getContentAnalysis()
    {
        // 1. Content distribution by status and role
        $contentDistribution = DB::table('posts')
            ->join('users', 'posts.Author', '=', 'users.UID')
            ->join('roles', 'users.Role', '=', 'roles.RoleName')
            ->select(
                'posts.Status as post_status',
                'roles.RoleName as author_role',
                DB::raw('COUNT(*) as post_count'),
                DB::raw('AVG(CHAR_LENGTH(posts.Content)) as avg_content_length'),
                DB::raw('MIN(CHAR_LENGTH(posts.Content)) as min_content_length'),
                DB::raw('MAX(CHAR_LENGTH(posts.Content)) as max_content_length'),
                DB::raw('COUNT(DISTINCT posts.Author) as unique_authors')
            )
            ->groupBy('posts.Status', 'roles.RoleName')
            ->orderBy('post_count', 'desc')
            ->get();

        // 2. Monthly content trends
        $monthlyTrends = DB::table('posts')
            ->join('users', 'posts.Author', '=', 'users.UID')
            ->where('posts.created_at', '>=', Carbon::now()->subYear())
            ->select(
                DB::raw('YEAR(posts.created_at) as year'),
                DB::raw('MONTH(posts.created_at) as month'),
                DB::raw('MONTHNAME(posts.created_at) as month_name'),
                DB::raw('COUNT(*) as posts_created'),
                DB::raw('COUNT(DISTINCT posts.Author) as active_authors'),
                DB::raw('COUNT(CASE WHEN posts.Status = "published" THEN 1 END) as published_posts'),
                DB::raw('COUNT(CASE WHEN posts.Status = "draft" THEN 1 END) as draft_posts'),
                DB::raw('AVG(CHAR_LENGTH(posts.Content)) as avg_content_length')
            )
            ->groupBy(
                DB::raw('YEAR(posts.created_at)'),
                DB::raw('MONTH(posts.created_at)'),
                DB::raw('MONTHNAME(posts.created_at)')
            )
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return [
            'content_distribution' => $contentDistribution,
            'monthly_trends' => $monthlyTrends
        ];
    }

    /**
     * Get user interaction patterns using complex JOINs
     */
    public function getUserInteractionPatterns()
    {
        return DB::table('users as main_user')
            ->join('posts as main_posts', 'main_user.UID', '=', 'main_posts.Author')
            ->leftJoin('posts as replies', 'main_posts.PostID', '=', 'replies.ParentPostID')
            ->leftJoin('users as repliers', 'replies.Author', '=', 'repliers.UID')
            ->join('roles as main_role', 'main_user.Role', '=', 'main_role.RoleName')
            ->leftJoin('roles as replier_role', 'repliers.Role', '=', 'replier_role.RoleName')
            ->where('main_posts.ParentPostID', null) // Only original posts
            ->where('main_posts.Status', 'published')
            ->select(
                'main_user.Username as author',
                'main_role.RoleName as author_role',
                DB::raw('COUNT(DISTINCT main_posts.PostID) as original_posts'),
                DB::raw('COUNT(DISTINCT replies.PostID) as total_replies_received'),
                DB::raw('COUNT(DISTINCT repliers.UID) as unique_repliers'),
                DB::raw('COUNT(CASE WHEN replier_role.RoleName = "admin" THEN 1 END) as admin_replies'),
                DB::raw('COUNT(CASE WHEN replier_role.RoleName = "moderator" THEN 1 END) as moderator_replies'),
                DB::raw('COUNT(CASE WHEN replier_role.RoleName = "member" THEN 1 END) as member_replies'),
                DB::raw('ROUND(COUNT(DISTINCT replies.PostID) / COUNT(DISTINCT main_posts.PostID), 2) as avg_replies_per_post'),
                DB::raw('MAX(replies.created_at) as last_reply_received')
            )
            ->groupBy('main_user.UID', 'main_user.Username', 'main_role.RoleName')
            ->having('original_posts', '>', 0)
            ->orderBy('total_replies_received', 'desc')
            ->limit(20)
            ->get();
    }
}
