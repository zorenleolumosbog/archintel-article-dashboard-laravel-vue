<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $articles = Article::when($request->search, function ($query) use($request) {
                    $query->where('title', 'LIKE', '%'.$request->search.'%')
                        ->orWhere('content', 'LIKE', '%'.$request->search.'%');
                })
                ->when($request->date, function ($query) use($request) {
                    $query->whereDate('date', $request->date);
                })
                ->when($request->status, function ($query) use($request) {
                    $query->where('status', $request->status);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->limit ? $request->limit : Article::count());
        
        return ArticleResource::collection($articles);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ArticleRequest $request)
    {
        $file = $request->file('image');
        $path = Storage::disk('public')->put("upload/" . Carbon::now()->toDateString() . "/" . Auth::user()->id, $file);

        $article = Article::create([
            'user_id' => Auth::user()->id,
            'company_id' => $request->company_id,
            'image' => Storage::disk('public')->url($path),
            'path' => $path,
            'title' => $request->title,
            'link' => $request->link,
            'date' => $request->date,
            'content' => $request->content,
            'status' => $request->status
        ]);

        return new ArticleResource($article);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function show(Article $article)
    {
        return new ArticleResource($article);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function update(ArticleRequest $request, Article $article)
    {
        $request->merge([
            'user_id' => Auth::user()->id,
        ]);

        $file = $request->file('image');
        if($file) {
            //Remove existing file
            Storage::disk('public')->delete($article->path);

            $path = Storage::disk('public')->put("upload/" . Carbon::now()->toDateString() . "/" . Auth::user()->id, $file);
    
            $article->update([
                'image' => Storage::disk('public')->url($path),
                'path' => $path
            ]);

            $data = $request->except(['image', 'path']);
        }

        $article->update($data);

        return new ArticleResource($article);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function destroy(Article $article)
    {
        $article->delete();
        
        return response(null, 204);
    }
}
