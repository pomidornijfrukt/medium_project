<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdvancedPostController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/posts/advanced-search",
     *     summary="Advanced post search with filtering and aggregation",
     *     tags={"Advanced Posts"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for title and content",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="tags",
     *         in="query",
     *         description="Comma-separated tag names to include",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="exclude_tags",
     *         in="query",
     *         description="Comma-separated tag names to exclude",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="author_role",
     *         in="query",
     *         description="Filter by author role",
     *         @OA\Schema(type="string", enum={"admin", "moderator", "member"})
     *     ),
     *     @OA\Parameter(
     *         name="min_replies",
     *         in="query",
     *         description="Minimum number of replies",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort criteria",
     *         @OA\Schema(type="string", enum={"recent", "popular", "replies", "engagement"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Advanced search results with aggregated data",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="posts", type="array", @OA\Items(
     *                     @OA\Property(property="post_id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="How to learn Laravel"),
     *                     @OA\Property(property="author", type="string", example="john_doe"),
     *                     @OA\Property(property="author_role", type="string", example="member"),
     *                     @OA\Property(property="reply_count", type="integer", example=5),
     *                     @OA\Property(property="unique_repliers", type="integer", example=3),
     *                     @OA\Property(property="tag_count", type="integer", example=2),
     *                     @OA\Property(property="engagement_score", type="number", example=8.5)
     *                 )),
     *                 @OA\Property(property="aggregations", type="object",
     *                     @OA\Property(property="total_results", type="integer", example=25),
     *                     @OA\Property(property="avg_replies", type="number", example=3.2),
     *                     @OA\Property(property="role_distribution", type="object")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function advancedSearch(Request $request)
    {
        $search = $request->get('search');
        $tags = $request->get('tags');
        $excludeTags = $request->get('exclude_tags');
        $authorRole = $request->get('author_role');
        $minReplies = $request->get('min_replies', 0);
        $sortBy = $request->get('sort_by', 'recent');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        try {
            // Build complex query with JOINs and aggregations using PostgreSQL-compatible syntax
            $query = Post::query()
                ->leftJoin('posts as replies', 'posts.PostID', '=', 'replies.ParentPostID')
                ->join('users as authors', 'posts.Author', '=', 'authors.UID')
                ->join('roles', 'authors.Role', '=', 'roles.RoleName')
                ->leftJoin('tag_is_used', 'posts.PostID', '=', 'tag_is_used.PostID')
                ->leftJoin('tags', 'tag_is_used.TagName', '=', 'tags.TagName')
                ->whereNull('posts.ParentPostID') // Only main posts
                ->where('posts.Status', 'published');

            // Apply search filter
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('posts.Topic', 'ILIKE', "%{$search}%")
                      ->orWhere('posts.Content', 'ILIKE', "%{$search}%");
                });
            }

            // Apply tag filter (include specific tags)
            if ($tags) {
                $tagArray = explode(',', $tags);
                $query->whereIn('tags.TagName', $tagArray);
            }

            // Apply exclude tags filter
            if ($excludeTags) {
                $excludeTagArray = explode(',', $excludeTags);
                $query->whereNotExists(function ($subQuery) use ($excludeTagArray) {
                    $subQuery->select(DB::raw(1))
                        ->from('tag_is_used as exclude_tag_usage')
                        ->join('tags as exclude_tags', 'exclude_tag_usage.TagName', '=', 'exclude_tags.TagName')
                        ->whereColumn('exclude_tag_usage.PostID', 'posts.PostID')
                        ->whereIn('exclude_tags.TagName', $excludeTagArray);
                });
            }

            // Apply author role filter
            if ($authorRole) {
                $query->where('authors.Role', $authorRole);
            }

            // Use PostgreSQL-compatible aggregation query
            $query->selectRaw('
                posts."PostID" as post_id,
                posts."Topic" as title,
                posts."Content" as content_preview,
                authors."Username" as author,
                authors."UID" as author_id,
                roles."RoleName" as author_role,
                posts.created_at,
                posts.updated_at,
                LENGTH(posts."Content") as content_length,
                COUNT(DISTINCT replies."PostID") as reply_count,
                COUNT(DISTINCT replies."Author") as unique_repliers,
                COUNT(DISTINCT tags."TagName") as tag_count,
                STRING_AGG(DISTINCT tags."TagName", \',\') as tag_list,
                COALESCE(MAX(replies.created_at), posts.created_at) as last_activity,
                EXTRACT(DAY FROM (NOW() - posts.created_at)) as days_since_created,
                ROUND(
                    (COUNT(DISTINCT replies."PostID") * 2) + 
                    (COUNT(DISTINCT replies."Author") * 3) + 
                    (COUNT(DISTINCT tags."TagName") * 1) + 
                    (CASE WHEN EXTRACT(DAY FROM (NOW() - posts.created_at)) < 7 THEN 5 ELSE 0 END)
                , 2) as engagement_score
            ');

            // Group by required fields for PostgreSQL
            $query->groupBy(
                'posts.PostID',
                'posts.Topic',
                'posts.Content',
                'authors.Username',
                'authors.UID',
                'roles.RoleName',
                'posts.created_at',
                'posts.updated_at'
            );

            // Apply minimum replies filter
            if ($minReplies > 0) {
                $query->havingRaw('COUNT(DISTINCT replies."PostID") >= ?', [$minReplies]);
            }

            // Apply sorting
            switch ($sortBy) {
                case 'popular':
                    $query->orderByRaw('COUNT(DISTINCT replies."PostID") DESC');
                    break;
                case 'replies':
                    $query->orderByRaw('COUNT(DISTINCT replies."PostID") DESC, COUNT(DISTINCT replies."Author") DESC');
                    break;
                case 'engagement':
                    $query->orderByRaw('
                        ROUND(
                            (COUNT(DISTINCT replies."PostID") * 2) + 
                            (COUNT(DISTINCT replies."Author") * 3) + 
                            (COUNT(DISTINCT tags."TagName") * 1) + 
                            (CASE WHEN EXTRACT(DAY FROM (NOW() - posts.created_at)) < 7 THEN 5 ELSE 0 END)
                        , 2) DESC
                    ');
                    break;
                case 'recent':
                default:
                    $query->orderByRaw('COALESCE(MAX(replies.created_at), posts.created_at) DESC');
                    break;
            }

            // Get total count for pagination using a simpler approach
            $countQuery = Post::query()
                ->leftJoin('tag_is_used', 'posts.PostID', '=', 'tag_is_used.PostID')
                ->leftJoin('tags', 'tag_is_used.TagName', '=', 'tags.TagName')
                ->join('users as authors', 'posts.Author', '=', 'authors.UID')
                ->whereNull('posts.ParentPostID')
                ->where('posts.Status', 'published');

            if ($search) {
                $countQuery->where(function($q) use ($search) {
                    $q->where('posts.Topic', 'ILIKE', "%{$search}%")
                      ->orWhere('posts.Content', 'ILIKE', "%{$search}%");
                });
            }

            if ($tags) {
                $tagArray = explode(',', $tags);
                $countQuery->whereIn('tags.TagName', $tagArray);
            }

            if ($excludeTags) {
                $excludeTagArray = explode(',', $excludeTags);
                $countQuery->whereNotExists(function ($subQuery) use ($excludeTagArray) {
                    $subQuery->select(DB::raw(1))
                        ->from('tag_is_used as exclude_tag_usage')
                        ->join('tags as exclude_tags', 'exclude_tag_usage.TagName', '=', 'exclude_tags.TagName')
                        ->whereColumn('exclude_tag_usage.PostID', 'posts.PostID')
                        ->whereIn('exclude_tags.TagName', $excludeTagArray);
                });
            }

            if ($authorRole) {
                $countQuery->where('authors.Role', $authorRole);
            }

            $total = $countQuery->distinct('posts.PostID')->count();

            // Apply pagination
            $offset = ($page - 1) * $perPage;
            $posts = $query->limit($perPage)->offset($offset)->get();

            // Truncate content preview and process tags
            $posts = $posts->map(function ($post) {
                $post->content_preview = strlen($post->content_preview) > 200 
                    ? substr($post->content_preview, 0, 200) . '...' 
                    : $post->content_preview;
                $post->tag_list = $post->tag_list ? explode(',', $post->tag_list) : [];
                return $post;
            });

            // Calculate aggregations
            $aggregations = $this->calculateSearchAggregations($total, $authorRole);

            return response()->json([
                'success' => true,
                'data' => [
                    'posts' => $posts,
                    'aggregations' => $aggregations,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'last_page' => ceil($total / $perPage)
                    ],
                    'filters_applied' => [
                        'search' => $search,
                        'tags' => $tags,
                        'exclude_tags' => $excludeTags,
                        'author_role' => $authorRole,
                        'min_replies' => $minReplies,
                        'sort_by' => $sortBy
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform advanced search',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/posts/trending",
     *     summary="Get trending posts using advanced analytics",
     *     tags={"Advanced Posts"},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for trending analysis",
     *         @OA\Schema(type="string", enum={"24h", "7d", "30d"}, default="7d")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Trending posts with analytics data"
     *     )
     * )
     */
    public function getTrendingPosts(Request $request)
    {
        $period = $request->get('period', '7d');
        
        $days = match($period) {
            '24h' => 1,
            '7d' => 7,
            '30d' => 30,
            default => 7
        };

        try {
            // Use a simpler approach that works with PostgreSQL
            $trendingPosts = Post::query()
                ->leftJoin('posts as replies', 'posts.PostID', '=', 'replies.ParentPostID')
                ->join('users as authors', 'posts.Author', '=', 'authors.UID')  
                ->join('roles', 'authors.Role', '=', 'roles.RoleName')
                ->leftJoin('tag_is_used', 'posts.PostID', '=', 'tag_is_used.PostID')
                ->leftJoin('tags', 'tag_is_used.TagName', '=', 'tags.TagName')
                ->whereNull('posts.ParentPostID')
                ->where('posts.Status', 'published')
                ->where('posts.created_at', '>=', now()->subDays($days))
                ->selectRaw('
                    posts."PostID" as post_id,
                    posts."Topic" as title,
                    authors."Username" as author,
                    roles."RoleName" as author_role,
                    posts.created_at,
                    COUNT(DISTINCT replies."PostID") as reply_count,
                    COUNT(DISTINCT replies."Author") as unique_repliers,
                    COUNT(DISTINCT tags."TagName") as tag_count,
                    STRING_AGG(DISTINCT tags."TagName", \',\') as tags,
                    ROUND(
                        (COUNT(DISTINCT replies."PostID") * 3) + 
                        (COUNT(DISTINCT replies."Author") * 5) + 
                        (COUNT(DISTINCT tags."TagName") * 2) + 
                        (CASE 
                            WHEN EXTRACT(DAY FROM (NOW() - posts.created_at)) <= 1 THEN 20
                            WHEN EXTRACT(DAY FROM (NOW() - posts.created_at)) <= 3 THEN 10
                            WHEN EXTRACT(DAY FROM (NOW() - posts.created_at)) <= 7 THEN 5
                            ELSE 1
                        END) +
                        (CASE WHEN roles."RoleName" IN (\'admin\', \'moderator\') THEN 3 ELSE 0 END)
                    , 2) as trending_score,
                    EXTRACT(DAY FROM (NOW() - posts.created_at)) as days_old
                ')
                ->groupBy('posts.PostID', 'posts.Topic', 'authors.Username', 'roles.RoleName', 'posts.created_at')
                ->orderBy('trending_score', 'desc')
                ->limit(20)
                ->get();

            // Process tags
            $trendingPosts = $trendingPosts->map(function ($post) {
                $post->tags = $post->tags ? explode(',', $post->tags) : [];
                return $post;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'trending_posts' => $trendingPosts,
                    'period' => $period,
                    'generated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trending posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/posts/recommendations/{userId}",
     *     summary="Get personalized post recommendations using collaborative filtering",
     *     tags={"Advanced Posts"},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID for recommendations",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Personalized post recommendations"
     *     )
     * )
     */
    public function getRecommendations(Request $request, $userId)
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        try {
            // Find user's posting patterns and interests using PostgreSQL-compatible syntax
            $userInterests = DB::table('posts as user_posts')
                ->join('tag_is_used', 'user_posts.PostID', '=', 'tag_is_used.PostID')
                ->where('user_posts.Author', $userId)
                ->selectRaw('
                    tag_is_used."TagName",
                    COUNT(*) as usage_count
                ')
                ->groupBy('tag_is_used.TagName')
                ->orderBy('usage_count', 'desc')
                ->limit(10)
                ->pluck('usage_count', 'TagName');

            if ($userInterests->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'recommendations' => [],
                        'user_interests' => [],
                        'message' => 'No user interests found for recommendations',
                        'algorithm' => 'tag_based_collaborative_filtering'
                    ]
                ]);
            }

            // Find posts with similar tags that user hasn't authored
            $recommendations = DB::table('posts as rec_posts')
                ->join('tag_is_used', 'rec_posts.PostID', '=', 'tag_is_used.PostID')
                ->join('users as authors', 'rec_posts.Author', '=', 'authors.UID')
                ->join('roles', 'authors.Role', '=', 'roles.RoleName')
                ->leftJoin('posts as replies', 'rec_posts.PostID', '=', 'replies.ParentPostID')
                ->whereNull('rec_posts.ParentPostID')
                ->where('rec_posts.Status', 'published')
                ->where('rec_posts.Author', '!=', $userId)
                ->whereIn('tag_is_used.TagName', array_keys($userInterests->toArray()))
                ->selectRaw('
                    rec_posts."PostID" as post_id,
                    rec_posts."Topic" as title,
                    authors."Username" as author,
                    roles."RoleName" as author_role,
                    rec_posts.created_at,
                    COUNT(DISTINCT replies."PostID") as reply_count,
                    COUNT(DISTINCT tag_is_used."TagName") as matching_tags,
                    STRING_AGG(DISTINCT tag_is_used."TagName", \',\') as tags,
                    COUNT(DISTINCT tag_is_used."TagName") as interest_score
                ')
                ->groupBy(
                    'rec_posts.PostID',
                    'rec_posts.Topic',
                    'authors.Username',
                    'roles.RoleName',
                    'rec_posts.created_at'
                )
                ->havingRaw('COUNT(DISTINCT tag_is_used."TagName") > 0')
                ->orderByRaw('COUNT(DISTINCT tag_is_used."TagName") DESC, COUNT(DISTINCT replies."PostID") DESC')
                ->limit(15)
                ->get();

            // Process recommendations
            $recommendations = $recommendations->map(function ($post) {
                $post->tags = $post->tags ? explode(',', $post->tags) : [];
                return $post;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'recommendations' => $recommendations,
                    'user_interests' => $userInterests,
                    'algorithm' => 'tag_based_collaborative_filtering'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate recommendations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate aggregations for search results
     */
    private function calculateSearchAggregations($total, $authorRole = null)
    {
        try {
            // Get role distribution from all posts
            $roleQuery = Post::query()
                ->join('users as authors', 'posts.Author', '=', 'authors.UID')
                ->join('roles', 'authors.Role', '=', 'roles.RoleName')
                ->whereNull('posts.ParentPostID')
                ->where('posts.Status', 'published');

            if ($authorRole) {
                $roleQuery->where('authors.Role', $authorRole);
            }

            $roleDistribution = $roleQuery
                ->selectRaw('roles."RoleName" as role, COUNT(DISTINCT posts."PostID") as count')
                ->groupBy('roles.RoleName')
                ->pluck('count', 'role');

            // Get average metrics
            $avgQuery = Post::query()
                ->leftJoin('posts as replies', 'posts.PostID', '=', 'replies.ParentPostID')
                ->whereNull('posts.ParentPostID')
                ->where('posts.Status', 'published');

            if ($authorRole) {
                $avgQuery->join('users as authors', 'posts.Author', '=', 'authors.UID')
                        ->where('authors.Role', $authorRole);
            }

            $avgMetrics = $avgQuery
                ->selectRaw('
                    AVG(LENGTH(posts."Content")) as avg_content_length,
                    AVG(reply_counts.reply_count) as avg_replies
                ')
                ->leftJoinSub(
                    DB::table('posts as sub_replies')
                        ->selectRaw('sub_replies."ParentPostID", COUNT(*) as reply_count')
                        ->whereNotNull('sub_replies.ParentPostID')
                        ->groupBy('sub_replies.ParentPostID'),
                    'reply_counts',
                    'posts.PostID',
                    '=',
                    'reply_counts.ParentPostID'
                )
                ->first();

            return [
                'total_results' => $total,
                'role_distribution' => $roleDistribution->toArray(),
                'avg_content_length' => round($avgMetrics->avg_content_length ?? 0, 2),
                'avg_replies' => round($avgMetrics->avg_replies ?? 0, 2)
            ];

        } catch (\Exception $e) {
            // Fallback to simple aggregations
            return [
                'total_results' => $total,
                'role_distribution' => [],
                'avg_content_length' => 0,
                'avg_replies' => 0
            ];
        }
    }
}
