<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Validator; // Import the Validator facade
use Illuminate\Support\Facades\Storage; // Import the Storage facade

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\ProductResource
     */
    public function index(Request $request)
    {
        // Add search functionality to API index
        $products = Product::with('category') // Eager load category if needed
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->q . '%')
                      ->orWhere('description', 'like', '%' . $request->q . '%')
                      ->orWhere('sku', 'like', '%' . $request->q . '%'); // Allow searching by SKU
            })
            ->latest() // Keep latest ordering as in your original
            ->paginate(10);

        // Assuming ProductResource expects success status, status code, message, and data
        return new ProductResource(true, 200, 'List Data Product', $products);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\App\Http\Resources\ProductResource
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug', // Ensure slug is unique
            'sku' => 'required|string|unique:products,sku', // Ensure SKU is unique
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'product_category_id' => 'nullable|exists:product_categories,id', // Check if category exists
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate image file
            'is_active' => 'boolean', // Validate is_active as boolean
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $product = new Product;
        $product->name = $request->name;
        $product->slug = $request->slug;
        $product->description = $request->description;
        $product->sku = $request->sku;
        $product->price = $request->price;
        $product->stock = $request->stock;
        $product->product_category_id = $request->product_category_id;
        // Use boolean helper for consistency
        $product->is_active = $request->has('is_active') ? $request->boolean('is_active') : true;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('uploads/products', $imageName, 'public');
            $product->image_url = $imagePath;
        }

        $product->save();

        // Assuming ProductResource expects success status, status code, message, and data
        return new ProductResource(true, 201, 'Product Created Successfully', $product);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \App\Http\Resources\ProductResource
     */
    public function show(string $id)
    {
        $product = Product::with('category')->findOrFail($id); // Eager load category
        // Assuming ProductResource expects success status, status code, message, and data
        return new ProductResource(true, 200, 'Product Details', $product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse|\App\Http\Resources\ProductResource
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // Unique slug, ignore current product's ID
            'slug' => 'required|string|max:255|unique:products,slug,' . $product->id,
            'description' => 'nullable|string',
            // Unique SKU, ignore current product's ID
            'sku' => 'required|string|unique:products,sku,' . $product->id,
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $product->name = $request->name;
        $product->slug = $request->slug;
        $product->description = $request->description;
        $product->sku = $request->sku;
        $product->price = $request->price; // Ensure price is updated
        $product->stock = $request->stock; // Ensure stock is updated
        $product->product_category_id = $request->product_category_id;
        // Use boolean helper for consistency, keep existing if not provided
        $product->is_active = $request->has('is_active') ? $request->boolean('is_active') : $product->is_active;

        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($product->image_url) {
                Storage::disk('public')->delete($product->image_url);
            }

            // Upload new image
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('uploads/products', $imageName, 'public');
            $product->image_url = $imagePath;
        }

        $product->save();

        // Assuming ProductResource expects success status, status code, message, and data
        return new ProductResource(true, 200, 'Product Updated Successfully', $product);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);

        // Delete associated image if it exists
        if ($product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }

        $product->delete();

        return response()->json(['success' => true, 'message' => 'Product Deleted Successfully'], 200);
    }
}