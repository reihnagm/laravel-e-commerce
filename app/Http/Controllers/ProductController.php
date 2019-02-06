<?php

namespace App\Http\Controllers;

use File;
use Toastr;
use Storage;

use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image;

use TCG\Voyager\Facades\Voyager;

use Illuminate\Http\Request;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\Category;

class ProductController extends Controller
{
    public function create(Request $request)
    {
        return view('product.create', ["user" => $request->user(), "categories" => Category::all()]);
    }

    public function store(ProductRequest $request)
    {

        // ALTERNATIVE STORING IMAGE BLOB 1
        // if ($request->hasFile('img')) {
        //      $img = $request->file('img');
        //      $filename = time(). "-" . $img->getClientOriginalName();
        //      $image = base64_encode(file_get_contents($request->file('img')));
        //      $blog->img = $image;
        //   }

        // ALTERNATIVE STORING IMAGE BLOB 2
        // if ($request->hasFile('img')) {
        //     $file =Input::file('img');
        //     $imagedata = file_get_contents($file);
        //     $base64 = 'data:image/jpeg;base64,'. base64_encode($imagedata);
        //     $blog->img = $base64;
        // }

        $slug = str_slug($request->name, '-');

        $product = Product::where('slug', $slug)->first();

        if ($product != null) {
            $slug = $slug . '-' .time();
        }

        // IF REQUEST ARRAY MULTIPLE
        // GETTING ERROR ARRAY TO STRING CONVERSION
        // $array  = $request->price; //
        // $price = implode('', $array); //

        // COPY FROM VOYAGER UPLOAD IMAGE
        $fullFilename = null;
        $resizeHeight = null;
        $resizeWidth = 1800;

        $file = $request->file('img');

        $path =  '/'.date('F').date('Y').'/';

        $filename = basename($file->getClientOriginalName().'-'.time(), '.'.$file->getClientOriginalExtension());

        $fullPath = 'products'.$path.$filename.'.'.$file->getClientOriginalExtension();

        $image = Image::make($file)->resize($resizeWidth, $resizeHeight, function (Constraint $constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode($file->getClientOriginalExtension(), 75);

        Storage::disk('public')->put($fullPath, (string) $image, 'public');

        $fullFilename = $fullPath;

        $product = Product::create([
        "name" => $request->name,
        "img"  => $fullFilename,
        "slug" => $slug,
        "desc" => $request->desc,
        "price" => $request->price,
        "user_id" => auth()->user()->id,
        ]);

        $product->category()->attach($request->categories);

        $product->save();

        auth()->user()->products($product);

        Toastr::info('Successfully created a Product!');

        return redirect(route('user.profile'));
    }

    public function update(ProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);

        $product_img = Product::findOrFail($id)->first();

        $oldImg = public_path("storage/{$product_img->img}");

        // REMOVED FILE EXISTS WHEN DELETE ACTION
        // AND GETTING NEW IMAGE
        if (File::exists($oldImg)) {
            unlink($oldImg);
        }

        $file = $request->file('img');

        // COPY FROM VOYAGER UPLOAD IMAGE
        $fullFilename = null;
        $resizeWidth = 1800;
        $resizeHeight = null;

        $file = $request->file('img');

        $path =  '/'.date('F').date('Y').'/';

        $filename = basename($file->getClientOriginalName().'-'.time(), '.'.$file->getClientOriginalExtension());

        $fullPath = 'products'.$path.$filename.'.'.$file->getClientOriginalExtension();

        $image = Image::make($file)->resize($resizeWidth, $resizeHeight, function (Constraint $constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode($file->getClientOriginalExtension(), 75);

        Storage::disk('public')->put($fullPath, (string) $image, 'public');

        $fullFilename = $fullPath;

        $slug = str_slug($request->name, '-');

        $product = Product::where('slug', $slug)->first();

        if ($product != null) {
            $slug = $slug . '-' .time();
        }

        $product->update([
        "name" => $request->name,
        "img" => $fullFilename ,
        "slug" => $slug,
        "desc" => $request->desc,
        "price" => $request->price,
        "user_id" => auth()->user()->id
        ]);

        // STORING IMAGE
        // if ($request->hasFile('img')) {
        //
        //     $img = $request->file('img');
        //
        //     $filename = time(). "-" . $img->getClientOriginalName();
        //
        //     $request->img->storeAs('public/products/images', $filename);
        //
        //     $blog->img = $filename;
        //
        // }

        $product->category()->sync($request->categories);

        auth()->user()->products()->save($product);

        Toastr::info('Successfully updated a Product!');

        return redirect(route('home'));
    }

    public function edit($id, Request $request)
    {
        return view('product.edit', ["user" => $request->user(),"product" =>  Product::where('slug', $id)->first(), "categories" => Category::all()]);
    }

    public function destroy($id)
    {
        $product = Product::where('slug', $id)->first();

        $storage = public_path("storage/{$product->img}");

        // IF NOT USE PACKAGE VOYAGER UNCOMMENT
        // $productImg = public_path("storage/products/images/{$product->img}");

        if (File::exists($storage)) {
            unlink($storage);
        }

        $product->delete();

        $product->category()->detach();

        Toastr::info('Successfully deleted a Product!');

        return redirect(route('home'));
    }
}
