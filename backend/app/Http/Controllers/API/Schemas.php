<?php

namespace App\Http\Controllers\API;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model",
 *     @OA\Property(property="UID", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(property="Username", type="string", example="johndoe"),
 *     @OA\Property(property="Email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="Role", type="string", example="user"),
 *     @OA\Property(property="Status", type="string", example="active"),
 *     @OA\Property(property="LastLoginAt", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Post",
 *     type="object",
 *     title="Post",
 *     description="Post model",
 *     @OA\Property(property="PostID", type="integer", example=1),
 *     @OA\Property(property="Author", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(property="Topic", type="string", example="How to learn Laravel"),
 *     @OA\Property(property="Content", type="string", example="This is the content of the post..."),
 *     @OA\Property(property="Status", type="string", example="published"),
 *     @OA\Property(property="LastEditedAt", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="ParentPostID", type="integer", nullable=true, example=null),
 *     @OA\Property(property="PostType", type="string", example="original"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="author", ref="#/components/schemas/User"),
 *     @OA\Property(property="tags", type="array", @OA\Items(ref="#/components/schemas/Tag"))
 * )
 * 
 * @OA\Schema(
 *     schema="Tag",
 *     type="object",
 *     title="Tag",
 *     description="Tag model",
 *     @OA\Property(property="TagName", type="string", example="laravel"),
 *     @OA\Property(property="Description", type="string", example="Laravel framework related posts"),
 *     @OA\Property(property="posts_count", type="integer", example=15),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     title="Validation Error",
 *     description="Validation error response",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Validation error"),
 *     @OA\Property(property="errors", type="object",
 *         @OA\Property(property="field_name", type="array", @OA\Items(type="string", example="The field is required."))
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     title="Success Response",
 *     description="Standard success response",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operation completed successfully"),
 *     @OA\Property(property="data", type="object")
 * )
 * 
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     title="Error Response",
 *     description="Standard error response",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="An error occurred")
 * )
 * 
 * @OA\Schema(
 *     schema="PaginatedResponse",
 *     type="object",
 *     title="Paginated Response",
 *     description="Paginated data response",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="data", type="array", @OA\Items(type="object")),
 *         @OA\Property(property="first_page_url", type="string", example="http://localhost/api/posts?page=1"),
 *         @OA\Property(property="from", type="integer", example=1),
 *         @OA\Property(property="last_page", type="integer", example=5),
 *         @OA\Property(property="last_page_url", type="string", example="http://localhost/api/posts?page=5"),
 *         @OA\Property(property="next_page_url", type="string", nullable=true, example="http://localhost/api/posts?page=2"),
 *         @OA\Property(property="path", type="string", example="http://localhost/api/posts"),
 *         @OA\Property(property="per_page", type="integer", example=15),
 *         @OA\Property(property="prev_page_url", type="string", nullable=true, example=null),
 *         @OA\Property(property="to", type="integer", example=15),
 *         @OA\Property(property="total", type="integer", example=67)
 *     )
 * )
 */
class Schemas
{
    // This class is only used for Swagger schema definitions
}
