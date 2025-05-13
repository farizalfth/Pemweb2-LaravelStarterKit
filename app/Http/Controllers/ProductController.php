<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * GET: dashboard/products
     * Menampilkan daftar produk.
     */
    public function index(Request $request)
    {
        $q = $request->q;

        $products = Product::with('category')
            ->when($q, function ($query, $q) {
                return $query->where('name', 'like', "%$q%")
                             ->orWhere('description', 'like', "%$q%");
            })
            ->paginate(10);

        return view('dashboard.products.index', compact('products', 'q'));
    }

    /**
     * GET: dashboard/products/create
     * Form tambah produk.
     */
    public function create()
    {
        $categories = Categories::all();
        return view('dashboard.products.create', compact('categories'));
    }

    /**
     * GET: dashboard/products/{id}
     * Menampilkan detail produk.
     */
    public function show(string $id)
    {
        $product = Product::findOrFail($id);
        return view('dashboard.products.show', compact('product'));
    }

    /**
     * GET: dashboard/products/{id}/edit
     * Form edit produk.
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $categories = Categories::all();

        return view('dashboard.products.edit', compact('product', 'categories'));
    }

    /**
     * POST: dashboard/products
     * Menyimpan produk baru.
     */
    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        $product = new Product($validated);

        if ($request->hasFile('image')) {
            $product->image = $this->handleImageUpload($request->file('image'));
        }

        $product->save();

        // Redirect ke halaman produk setelah berhasil menambah
        return redirect()->route('products.index')->with('successMessage', 'Product Berhasil Disimpan');
    }

    /**
     * PUT/PATCH: dashboard/products/{id}
     * Memperbarui produk.
     */
    public function update(Request $request, string $id)
    {
        $validated = $this->validateRequest($request);

        $product = Product::findOrFail($id);
        $product->fill($validated);

        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            // Upload gambar baru
            $product->image = $this->handleImageUpload($request->file('image'));
        }

        $product->save();

        // Redirect ke halaman produk setelah berhasil mengupdate
        return redirect()->route('products.index')->with('successMessage', 'Product Berhasil Diperbarui');
    }

    /**
     * DELETE: dashboard/products/{id}
     * Menghapus produk.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);

        // Hapus gambar produk jika ada
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        // Hapus produk dari database
        $product->delete();

        // Redirect ke halaman produk setelah berhasil dihapus
        return redirect()->route('products.index')->with('successMessage', 'Data Berhasil Dihapus');
    }

    /**
     * Validasi input produk.
     */
    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'product_category_id' => 'nullable|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
    }

    /**
     * Upload file gambar ke storage.
     */
    private function handleImageUpload($image): string
    {
        $imageName = time() . '_' . $image->getClientOriginalName();
        return $image->storeAs('uploads/products', $imageName, 'public');
    }
}