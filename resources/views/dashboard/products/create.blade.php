<x-layouts.app :title="__('Add New Product')">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl">Add New Product</flux:heading>
        <flux:subheading size="lg" class="mb-6">Create a new product</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Input for product name -->
        <flux:input label="Name" name="name" value="{{ old('name') }}" required class="mb-3" />

        <!-- Category Dropdown -->
        <div class="mb-3">
            <label for="product_category_id" class="block text-white font-semibold mb-2">
                Category
            </label>
            <select name="product_category_id" id="product_category_id" required class="w-full p-2 rounded-md bg-zinc-700 text-white appearance-none">
                <option value="" disabled {{ old('product_category_id') ? '' : 'selected' }}>Select Category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('product_category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Textarea for product description -->
        <flux:textarea label="Description" name="description" required class="mb-3">
            {{ old('description') }}
        </flux:textarea>

        <!-- Input for product price -->
        <flux:input label="Price" name="price" type="number" value="{{ old('price') }}" required class="mb-3" />

        <!-- Product Stock -->
        <flux:input label="Stock" name="stock" type="number" value="{{ old('stock') }}" class="mb-3" />

        <!-- Image Product -->
        <flux:input type="file" name="image" label="Upload Product Image" />

        <div class="mt-4">
            <flux:button type="submit" variant="primary">Save</flux:button>
            <flux:link href="{{ route('products.index') }}" variant="ghost" class="ml-3">Cancel</flux:link>
        </div>
    </form>

</x-layouts.app>