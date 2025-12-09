<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\Post::all());
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $post = \App\Models\Post::create([
            'content' => $validated['content'],
            'x' => rand(-2000, 2000),
            'y' => rand(-2000, 2000),
        ]);

        return response()->json($post);
    }
}
