<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    /**
     * Display a listing of posts.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Post::with(['author', 'tags'])
            ->where('Status', 'published')
            ->orderBy('created_at', 'desc');

        // Handle search by topic
        if ($request->has('search')) {
            $query->where('Topic', 'like', '%' . $request->search . '%')
                  ->orWhere('Content', 'like', '%' . $request->search . '%');
        }

        $posts = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    /**
     * Store a newly created post.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic' => 'required|string|max:255',
            'content' => 'required|string',
            'tags' => 'array',
            'tags.*' => 'string|exists:tags,TagName',
            'status' => 'sometimes|string|in:draft,published',
            'parent_post_id' => 'sometimes|integer|exists:posts,PostID'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if this is a linked post (a "comment")
        $isLinkedPost = $request->has('parent_post_id');
        $postType = $isLinkedPost ? 'linked' : 'main';
        
        // If it's a linked post, check if the parent exists and is published
        if ($isLinkedPost) {
            $parentPost = Post::find($request->parent_post_id);
            
            if (!$parentPost) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent post not found'
                ], 404);
            }
            
            // Only allow linking to main posts, not to other linked posts
            if ($parentPost->PostType === 'linked') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot create a linked post to another linked post'
                ], 422);
            }
        }

        $post = Post::create([
            'Author' => $request->user()->UID,
            'Topic' => $request->topic,
            'Content' => $request->content,
            'Status' => $request->status ?? 'draft',
            'ParentPostID' => $isLinkedPost ? $request->parent_post_id : null,
            'PostType' => $postType
        ]);

        // Attach tags
        if ($request->has('tags')) {
            $post->tags()->attach($request->tags);
        }

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'data' => $post->load(['author', 'tags'])
        ], 201);
    }    /**
     * Display a specific post.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $post = Post::with(['author', 'tags'])
            ->where('PostID', $id)
            ->where(function ($query) {
                $query->where('Status', 'published')
                    ->orWhere(function ($q) {
                        // If user is authenticated and is the author
                        if (auth()->check()) {
                            $q->where('Author', auth()->user()->UID);
                        }
                    });
            })
            ->first();

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }
        
        // Load linked posts (comments) if this is a main post
        if ($post->PostType === 'main') {
            // If user is authenticated and is the author or admin, show all linked posts
            if (auth()->check() && (auth()->user()->UID === $post->Author || auth()->user()->Role === 'admin')) {
                $post->load('allLinkedPosts.author');
            } else {
                // Otherwise only show published linked posts
                $post->load('linkedPosts.author');
            }
        }
        
        // Load parent post if this is a linked post
        if ($post->PostType === 'linked' && $post->ParentPostID) {
            $post->load('parent');
        }

        return response()->json([
            'success' => true,
            'data' => $post
        ]);
    }    /**
     * Update a post.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        // Check if user is allowed to update
        if ($post->Author !== $request->user()->UID && $request->user()->Role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this post'
            ], 403);
        }

        $validationRules = [
            'topic' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|exists:tags,TagName',
            'status' => 'sometimes|string|in:draft,published,deleted',
        ];

        // If it's a linked post, allow updating parent_post_id
        if ($post->PostType === 'linked') {
            $validationRules['parent_post_id'] = 'sometimes|integer|exists:posts,PostID';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // If changing parent_post_id, validate the new parent
        if ($request->has('parent_post_id') && $post->PostType === 'linked') {
            $newParent = Post::find($request->parent_post_id);
            
            if (!$newParent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent post not found'
                ], 404);
            }
            
            // Only allow linking to main posts, not to other linked posts
            if ($newParent->PostType === 'linked') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot link to another linked post'
                ], 422);
            }
        }

        $updateData = [
            'Topic' => $request->topic ?? $post->Topic,
            'Content' => $request->content ?? $post->Content,
            'Status' => $request->status ?? $post->Status,
            'LastEditedAt' => now(),
        ];

        // Add parent_post_id to update data if provided and post is a linked post
        if ($request->has('parent_post_id') && $post->PostType === 'linked') {
            $updateData['ParentPostID'] = $request->parent_post_id;
        }

        $post->update($updateData);

        // Update tags if provided
        if ($request->has('tags')) {
            $post->tags()->sync($request->tags);
        }

        // Load parent if this is a linked post
        if ($post->PostType === 'linked') {
            $post->load('parent');
        }

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully',
            'data' => $post->load(['author', 'tags'])
        ]);
    }    /**
     * Remove a post.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        // Check if user is allowed to delete
        if ($post->Author !== $request->user()->UID && $request->user()->Role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this post'
            ], 403);
        }

        // Soft delete
        $post->update([
            'Status' => 'deleted',
            'LastEditedAt' => now(),
        ]);

        // If this is a main post, soft delete all linked posts too
        if ($post->PostType === 'main') {
            $post->allLinkedPosts()->update([
                'Status' => 'deleted',
                'LastEditedAt' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully'
        ]);
    }/**
     * Get posts by tag.
     *
     * @param string $tag
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByTag($tag)
    {
        $tagModel = Tag::find($tag);

        if (!$tagModel) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found'
            ], 404);
        }

        $posts = $tagModel->posts()
            ->where('Status', 'published')
            ->with(['author', 'tags'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => [
                'tag' => $tagModel,
                'posts' => $posts
            ]
        ]);
    }

    /**
     * Get linked posts (comments) for a specific post.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLinkedPosts($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        // Check if this is a main post
        if ($post->PostType !== 'main') {
            return response()->json([
                'success' => false,
                'message' => 'Linked posts can only be retrieved for main posts'
            ], 422);
        }

        // Determine which linked posts to show based on user authorization
        if (auth()->check() && (auth()->user()->UID === $post->Author || auth()->user()->Role === 'admin')) {
            // If user is author or admin, show all linked posts with their authors
            $linkedPosts = $post->allLinkedPosts()->with('author')->paginate(20);
        } else {
            // Otherwise, only show published linked posts with their authors
            $linkedPosts = $post->linkedPosts()->with('author')->paginate(20);
        }

        return response()->json([
            'success' => true,
            'data' => $linkedPosts
        ]);
    }
}
