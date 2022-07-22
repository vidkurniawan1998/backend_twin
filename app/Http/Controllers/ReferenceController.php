<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReferenceStoreRequest;
use App\Http\Requests\ReferenceUpdateRequest;
use App\Http\Resources\Reference as ReferenceResources;
use App\Models\Reference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferenceController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($this->user->can('Menu Reference')):
            $keyword = $request->keyword;
            $perPage = $request->per_page;

            $references = Reference::when($keyword <> '', function ($q) use ($keyword) {
                return $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('value', 'like', "%{$keyword}%")
                    ->orWhere('notes', 'like', "%{$keyword}%");
            })->orderBy('id', 'desc');

            $references = $perPage == 'all' ? $references->get() : $references->paginate($perPage);

            return ReferenceResources::collection($references);
        else:
            return $this->unAuthorized();
        endif;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ReferenceStoreRequest $request)
    {
        if ($this->user->can('Tambah Reference')):
            $data = $request->all();
            return Reference::create($data) ? $this->storeTrue('reference') : $this->storeFalse('reference');
        else:
            return $this->unAuthorized();
        endif;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if ($this->user->can('Edit Reference')):
            $reference = Reference::find($id);
            if ($reference) {
                return new ReferenceResources($reference);
            }

            return $this->dataNotFound('reference');
        else:
            return $this->unAuthorized();
        endif;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ReferenceUpdateRequest $request, $id)
    {
        if ($this->user->can('Update Reference')):
            $reference = Reference::find($id);
            if ($reference) {
                $data = $request->except(['id']);
                return $reference->update($data) ? $this->updateTrue('reference') : $this->updateFalse('reference');
            }

            return $this->dataNotFound('reference');
        else:
            return $this->unAuthorized();
        endif;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if ($this->user->can('Delete Reference')):
            $reference = Reference::find($id);
            if ($reference) {
                return $reference->delete() ? $this->destroyTrue('reference') : $this->destroyFalse('reference');
            }

            return $this->dataNotFound('reference');
        else:
            return $this->unAuthorized();
        endif;
    }

    public function findByCode(Request $request)
    {
        $code = $request->code;
        if ($code) {
            if (is_array($code)) {
                $references = Reference::whereIn('code', $code)->get();
                return ReferenceResources::collection($references);
            } else {
                $reference  = Reference::where('code', $code)->first();
                return new ReferenceResources($reference);
            }
        }

        return $this->dataNotFound('reference');
    }
}
