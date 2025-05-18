<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    /**
     * Display a listing of tags.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Tag::query();

        // Handle search by tag name
        if ($request->has('search')) {
            $query->where('TagName', 'like', '%' . $request->search . '%')
                  ->orWhere('Description', 'like', '%' . $request->search . '%');
        }

        $tags = $query->withCount('posts')->get();

        return response()->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    /**
     * Store a newly created tag.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Only admin and moderator can create tags
        if ($request->user()->Role !== 'admin' && $request->user()->Role !== 'moderator') {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to create tags'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:tags,TagName',
            'description' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $tag = Tag::create([
            'TagName' => $request->name,
            'Description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tag created successfully',
            'data' => $tag
        ], 201);
    }

    /**
     * Display a specific tag.
     *
     * @param string $tagName
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($tagName)
    {
        $tag = Tag::with('posts')->withCount('posts')->find($tagName);

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tag
        ]);
    }

    /**
     * Update a tag.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $tagName
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $tagName)
    {
        // Only admin and moderator can update tags
        if ($request->user()->Role !== 'admin' && $request->user()->Role !== 'moderator') {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update tags'
            ], 403);
        }

        $tag = Tag::find($tagName);

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $tag->update([
            'Description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tag updated successfully',
            'data' => $tag
        ]);
    }

    /**
     * Remove a tag.
     *
     * @param string $tagName
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $tagName)
    {
        // Only admin can delete tags
        if ($request->user()->Role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete tags'
            ], 403);
        }

        $tag = Tag::find($tagName);

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found'
            ], 404);
        }

        // Check if tag is in use
        if ($tag->posts()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a tag that is associated with posts'
            ], 409);
        }

        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tag deleted successfully'
        ]);
    }
}
