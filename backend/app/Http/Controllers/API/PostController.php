<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/posts",
     *     summary="Get all published posts",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search posts by topic or content",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Posts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Post")),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Post::with(['author', 'tags'])
                ->where('Status', 'published')
                ->orderBy('created_at', 'desc');

            // Handle search functionality
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('Topic', 'like', '%' . $searchTerm . '%')
                      ->orWhere('Content', 'like', '%' . $searchTerm . '%');
                });
            }

            $posts = $query->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'Posts retrieved successfully',
                'data' => $posts
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/posts",
     *     summary="Create a new post",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"topic","content"},
     *             @OA\Property(property="topic", type="string", example="How to learn Laravel"),
     *             @OA\Property(property="content", type="string", example="This is the content of the post..."),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string"), example={"laravel", "php"}),
     *             @OA\Property(property="status", type="string", enum={"draft", "published"}, example="published"),
     *             @OA\Property(property="parent_post_id", type="integer", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Post created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Post created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Post")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            // Validation rules
            $validator = Validator::make($request->all(), [
                'topic' => 'required|string|max:255',
                'content' => 'required|string',
                'status' => 'in:draft,published',
                'tags' => 'array',
                'tags.*' => 'string|max:50',
                'parent_post_id' => 'nullable|exists:posts,PostID'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Create the post
            $postData = [
                'Author' => Auth::id(),
                'Topic' => $request->topic,
                'Content' => $request->content,
                'Status' => $request->status ?? 'published',
                'LastEditedAt' => now()
            ];
            
            // Only add ParentPostID and PostType if columns exist
            if ($request->has('parent_post_id')) {
                $postData['ParentPostID'] = $request->parent_post_id;
                $postData['PostType'] = $request->parent_post_id ? 'linked' : 'main';
            }
            
            $post = Post::create($postData);

            // Handle tags if provided
            if ($request->has('tags') && is_array($request->tags)) {
                $tagIds = [];
                foreach ($request->tags as $tagName) {
                    $tag = Tag::firstOrCreate(['TagName' => $tagName]);
                    $tagIds[] = $tag->TagName;
                }
                $post->tags()->sync($tagIds);
            }

            DB::commit();

            // Load relationships for response
            $post->load(['author', 'tags']);

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully',
                'data' => $post
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/posts/{id}",
     *     summary="Get a specific post",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Post ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Post")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $post = Post::with(['author', 'tags'])->where('PostID', $id)->first();

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found'
                ], 404);
            }

            // Get all linked posts recursively and attach them to the post
            $linkedPosts = $this->getAllLinkedPostsRecursively($id);
            $post->linkedPosts = collect($linkedPosts);

            return response()->json([
                'success' => true,
                'message' => 'Post retrieved successfully',
                'data' => $post
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/posts/{id}",
     *     summary="Update a post",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Post ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="topic", type="string", example="Updated topic"),
     *             @OA\Property(property="content", type="string", example="Updated content..."),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string"), example={"laravel", "updated"}),
     *             @OA\Property(property="status", type="string", enum={"draft", "published"}, example="published")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Post updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Post")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to update this post",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $post = Post::where('PostID', $id)->first();

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found'
                ], 404);
            }

            // Check if user owns the post or is admin
            $user = Auth::user()->load('role');
            $isAdmin = $user->role && $user->role->RoleName === 'admin';
            
            if ($post->Author !== Auth::id() && !$isAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to update this post'
                ], 403);
            }

            // Validation rules
            $validator = Validator::make($request->all(), [
                'topic' => 'sometimes|required|string|max:255',
                'content' => 'sometimes|required|string',
                'status' => 'sometimes|in:draft,published',
                'tags' => 'sometimes|array',
                'tags.*' => 'string|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Update post fields
            $updateData = [];
            if ($request->has('topic')) $updateData['Topic'] = $request->topic;
            if ($request->has('content')) $updateData['Content'] = $request->content;
            if ($request->has('status')) $updateData['Status'] = $request->status;
            $updateData['LastEditedAt'] = now();

            $post->update($updateData);

            // Handle tags if provided
            if ($request->has('tags') && is_array($request->tags)) {
                $tagIds = [];
                foreach ($request->tags as $tagName) {
                    $tag = Tag::firstOrCreate(['TagName' => $tagName]);
                    $tagIds[] = $tag->TagName;
                }
                $post->tags()->sync($tagIds);
            }

            DB::commit();

            // Load relationships for response
            $post->load(['author', 'tags']);

            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully',
                'data' => $post
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/posts/{id}",
     *     summary="Delete a post",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Post ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Post deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to delete this post",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $post = Post::where('PostID', $id)->first();

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found'
                ], 404);
            }

            // Check if user owns the post
            if ($post->Author !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this post'
                ], 403);
            }

            DB::beginTransaction();

            // Detach tags
            $post->tags()->detach();
            
            // Delete the post
            $post->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/posts/tag/{tag}",
     *     summary="Get posts by tag",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="tag",
     *         in="path",
     *         description="Tag name",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Posts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Post"))
     *         )
     *     )
     * )
     */
    public function getByTag($tagName)
    {
        try {
            $posts = Post::with(['author', 'tags'])
                ->whereHas('tags', function($query) use ($tagName) {
                    $query->where('TagName', $tagName);
                })
                ->where('Status', 'published')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'Posts retrieved successfully',
                'data' => $posts
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve posts by tag',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/posts/user/{userId}",
     *     summary="Get posts by user",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Posts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Post"))
     *         )
     *     )
     * )
     */
    public function getUserPosts($userId)
    {
        try {
            $posts = Post::with(['author', 'tags'])
                ->where('Author', $userId)
                ->where('Status', 'published')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'User posts retrieved successfully',
                'data' => $posts
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/user/posts",
     *     summary="Get current user's posts",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Posts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Post"))
     *         )
     *     )
     * )
     */
    public function getMyPosts()
    {
        try {
            $posts = Post::with(['author', 'tags'])
                ->where('Author', Auth::id())
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'Your posts retrieved successfully',
                'data' => $posts
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve your posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/posts/{id}/linked",
     *     summary="Get linked posts (comments) for a post",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Post ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Linked posts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Post"))
     *         )
     *     )
     * )
     */
    public function getLinkedPosts($id)
    {
        try {
            $post = Post::where('PostID', $id)->first();

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found'
                ], 404);
            }

            // Get all linked posts recursively (unlimited depth)
            $allLinkedPosts = $this->getAllLinkedPostsRecursively($id);

            return response()->json([
                'success' => true,
                'message' => 'Linked posts retrieved successfully',
                'data' => $allLinkedPosts
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve linked posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recursively get all linked posts for unlimited depth
     */
    private function getAllLinkedPostsRecursively($parentId, &$collectedPosts = [], $maxDepth = 10, $currentDepth = 0)
    {
        // Prevent infinite recursion
        if ($currentDepth >= $maxDepth) {
            return $collectedPosts;
        }

        // Get direct replies to this parent
        $directReplies = Post::with(['author', 'tags'])
            ->where('ParentPostID', $parentId)
            ->where('Status', 'published')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($directReplies as $reply) {
            $collectedPosts[] = $reply;
            
            // Recursively get nested replies
            $this->getAllLinkedPostsRecursively(
                $reply->PostID, 
                $collectedPosts, 
                $maxDepth, 
                $currentDepth + 1
            );
        }

        return $collectedPosts;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/posts",
     *     summary="Get all posts for admin (including drafts and deleted)",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Posts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Post"))
     *         )
     *     )
     * )
     */
    public function getAllForAdmin()
    {
        try {
            $posts = Post::with(['author', 'tags'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Posts retrieved successfully',
                'data' => $posts
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
