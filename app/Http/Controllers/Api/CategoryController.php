<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $categories = Category::paginate(10);

        return response()
            ->json([
                "message" => "Get categories successfully.",
                "data" => $categories
            ])
            ->setStatusCode(200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): JsonResponse
    {
        request()->validate([
            'name' => 'required|unique:categories,name'
        ]);

        $category = new Category();
        $category->name = request()->input('name');
        $category->save();

        return response()
            ->json([
                'message' => 'Category created successfully.',
                'data' => $category
            ])
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()
                ->json(['message' => 'Category not found'])
                ->setStatusCode(404);
        }

        return response()
            ->json([
                'message' => 'Get category successfully.',
                'data' => $category
            ])
            ->setStatusCode(200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()
                ->json(['message' => 'Category not found'])
                ->setStatusCode(404);
        }

        request()->validate([
            "name" => ["required", "unique:categories,name,$id"]
        ]);

        $category->name = request()->input('name');
        $category->save();

        return response()
            ->json([
                'message' => 'Category updated successfully.',
                'data' => $category
            ])
            ->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()
                ->json(['message' => 'Category not found'])
                ->setStatusCode(404);
        }

        $category->delete();
        return response()
            ->json([])
            ->setStatusCode(204);
    }
}
