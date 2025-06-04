<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    /**
     * @OA\Get(
     *     path="/tags",
     *     summary="Display a listing of tags",
     *     tags={"Tags"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search tags by name or description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tags retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Tag"))
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/tags",
     *     summary="Store a newly created tag",
     *     tags={"Tags"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tag_name","description"},
     *             @OA\Property(property="tag_name", type="string", example="laravel"),
     *             @OA\Property(property="description", type="string", example="Laravel framework related posts")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tag created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tag created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Tag")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to create tags",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * Store a newly created tag.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Only admin and moderator can create tags (optional authentication check)
        if ($request->user() && $request->user()->Role !== 'admin' && $request->user()->Role !== 'moderator') {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to create tags'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:tags,TagName',
            'description' => 'nullable|string|max:255', // Made optional for auto-fill
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
            'Description' => $request->description, // Will auto-fill in model if empty
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tag created successfully',
            'data' => $tag
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/tags/{tagName}",
     *     summary="Display a specific tag",
     *     tags={"Tags"},
     *     @OA\Parameter(
     *         name="tagName",
     *         in="path",
     *         description="Tag name",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", allOf={
     *                 @OA\Schema(ref="#/components/schemas/Tag"),
     *                 @OA\Schema(
     *                     @OA\Property(property="posts", type="array", @OA\Items(ref="#/components/schemas/Post")),
     *                     @OA\Property(property="posts_count", type="integer", example=15)
     *                 )
     *             })
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tag not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Put(
     *     path="/tags/{tagName}",
     *     summary="Update a tag",
     *     tags={"Tags"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="tagName",
     *         in="path",
     *         description="Tag name",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"description"},
     *             @OA\Property(property="description", type="string", example="Updated description for Laravel framework related posts")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tag updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Tag")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tag not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to update tags",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Delete(
     *     path="/tags/{tagName}",
     *     summary="Remove a tag",
     *     tags={"Tags"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="tagName",
     *         in="path",
     *         description="Tag name",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag deleted successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tag not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Cannot delete a tag that is associated with posts",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to delete tags - Admin access required",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
